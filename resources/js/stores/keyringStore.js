import { defineStore } from 'pinia';

const channels = new Map();
const editabilityListeners = new Set();
let echoFactory = null;
let connectionBound = false;
let pageLifecycleBound = false;

function keyFor(type, id) {
    return `${type}:${Number(id)}`;
}

function csrfToken() {
    return globalThis.document?.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function freeStatus() {
    return { locked: false, lock_placed: false, lock_id: null, locked_by_id: null, locked_by_name: null, locked_by_avatar: null, expires_at: null };
}

function jitter() {
    return Math.floor(Math.random() * 500);
}

export const useKeyringStore = defineStore('keyrings', {
    state: () => ({
        config: { base_url: '/keyrings', user_id: null, can_steal: false },
        entries: {},
    }),
    getters: {
        entry: (state) => (type, id) => state.entries[keyFor(type, id)] ?? null,
        isLockedByOther: (state) => (type, id) => Boolean(state.entries[keyFor(type, id)]?.status?.locked),
        holder: (state) => (type, id) => {
            const status = state.entries[keyFor(type, id)]?.status;
            return status?.locked_by_id ? { id: status.locked_by_id, name: status.locked_by_name, avatar_url: status.locked_by_avatar } : null;
        },
        viewers: (state) => (type, id) => state.entries[keyFor(type, id)]?.viewers ?? [],
    },
    actions: {
        configure(config, echo = null) {
            this.config = { ...this.config, ...(config ?? {}) };
            echoFactory = echo;
            this.bindPageLifecycle();
        },
        url(type, id, suffix = '') {
            return `${String(this.config.base_url).replace(/\/$/, '')}/${type}/${Number(id)}${suffix}`;
        },
        async acquire(type, id, initialStatus = null) {
            if (!type || !id) return null;
            const key = keyFor(type, id);
            if (!this.entries[key]) {
                this.entries[key] = { type, id: Number(id), status: initialStatus ?? null, viewers: [], stolenBy: null, pending: false, error: '' };
                this.join(type, id);
            }

            return this.request(type, id, 'POST');
        },
        async steal(type, id) {
            if (!this.config.can_steal || !this.entries[keyFor(type, id)]) return null;

            return this.request(type, id, 'POST', '/steal');
        },
        async release(type, id) {
            const key = keyFor(type, id);
            const entry = this.entries[key];
            if (!entry) return;
            delete this.entries[key];
            this.leave(type, id);
            if (!entry.status?.lock_placed) return;

            try {
                await fetch(this.url(type, id), { method: 'DELETE', keepalive: true, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() } });
            } catch {
                // The backstop sweep or the next viewer's takeover frees a keyring that could not be released here.
            }
        },
        async request(type, id, method, suffix = '') {
            const entry = this.entries[keyFor(type, id)];
            entry.pending = true;
            entry.error = '';

            try {
                const response = await fetch(this.url(type, id, suffix), {
                    method,
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || 'De vergrendeling kon niet worden opgevraagd.');
                const current = this.entries[keyFor(type, id)];
                if (!current) return null;
                current.status = payload.data;
                if (payload.data.lock_placed) current.stolenBy = null;

                return payload.data;
            } catch (error) {
                const current = this.entries[keyFor(type, id)];
                if (current) current.error = error.message;

                return null;
            } finally {
                const current = this.entries[keyFor(type, id)];
                if (current) current.pending = false;
            }
        },
        applyStatus(type, id, status) {
            const entry = this.entries[keyFor(type, id)];
            if (entry && status) entry.status = { ...freeStatus(), ...status };
        },
        applyChanged(type, id, event) {
            const entry = this.entries[keyFor(type, id)];
            if (!entry) return;
            const holder = event.holder;
            const ownId = Number(this.config.user_id);

            if (!holder) {
                entry.status = freeStatus();
                this.scheduleAcquire(type, id);
                return;
            }

            const heldByMe = Number(holder.holder_id) === ownId;
            entry.status = {
                locked: !heldByMe,
                lock_placed: heldByMe,
                lock_id: holder.lock_id,
                locked_by_id: holder.holder_id,
                locked_by_name: holder.name,
                locked_by_avatar: holder.avatar_url,
                expires_at: holder.expires_at,
            };
            if (heldByMe) {
                entry.stolenBy = null;
            } else if (event.reason === 'stolen' && Number(event.previous_holder_id) === ownId) {
                entry.stolenBy = holder.name;
            }
        },
        applyNotEditable(payload) {
            if (payload?.reason === 'keyring' && payload.keyring?.type) {
                const entry = this.entries[keyFor(payload.keyring.type, payload.keyring.type_id)];
                if (entry) {
                    const wasHolder = Boolean(entry.status?.lock_placed);
                    this.applyStatus(payload.keyring.type, payload.keyring.type_id, payload.keyring);
                    if (wasHolder && payload.keyring.locked) entry.stolenBy = payload.keyring.locked_by_name;
                }
            }

            return payload?.reason && payload.reason !== 'keyring' ? payload.reason : null;
        },
        isReadOnly(reasons, type, id) {
            return projectReasons(reasons).length > 0 || Boolean(id && this.isLockedByOther(type, id));
        },
        setViewers(type, id, members) {
            const entry = this.entries[keyFor(type, id)];
            if (entry) entry.viewers = members.filter((member) => Number(member.id) !== Number(this.config.user_id));
        },
        addViewer(type, id, member) {
            const entry = this.entries[keyFor(type, id)];
            if (!entry || Number(member.id) === Number(this.config.user_id)) return;
            entry.viewers = [...entry.viewers.filter((viewer) => Number(viewer.id) !== Number(member.id)), member];
        },
        removeViewer(type, id, member) {
            const entry = this.entries[keyFor(type, id)];
            if (!entry) return;
            entry.viewers = entry.viewers.filter((viewer) => Number(viewer.id) !== Number(member.id));
            if (entry.status?.locked && Number(entry.status.locked_by_id) === Number(member.id)) this.scheduleAcquire(type, id);
        },
        scheduleAcquire(type, id) {
            setTimeout(() => {
                const entry = this.entries[keyFor(type, id)];
                if (entry && !entry.status?.lock_placed && !entry.pending) this.request(type, id, 'POST');
            }, jitter());
        },
        reacquireAll() {
            for (const entry of Object.values(this.entries)) {
                if (!entry.pending) this.request(entry.type, entry.id, 'POST');
            }
        },
        releaseAllOnPageHide() {
            for (const entry of Object.values(this.entries)) {
                if (!entry.status?.lock_placed) continue;
                const body = new FormData();
                body.append('_token', csrfToken());
                const sent = entry.status.lock_id && typeof navigator !== 'undefined' && typeof navigator.sendBeacon === 'function'
                    && navigator.sendBeacon(`${String(this.config.base_url).replace(/\/$/, '')}/${entry.status.lock_id}/release`, body);
                if (!sent) {
                    fetch(this.url(entry.type, entry.id), { method: 'DELETE', keepalive: true, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() } }).catch(() => {});
                }
                entry.status = { ...entry.status, lock_placed: false };
            }
        },
        onEditabilityChanged(listener) {
            editabilityListeners.add(listener);

            return () => editabilityListeners.delete(listener);
        },
        echo() {
            if (!echoFactory) return null;
            try {
                const echo = echoFactory();
                this.bindConnection(echo);

                return echo;
            } catch {
                return null;
            }
        },
        join(type, id) {
            const echo = this.echo();
            if (!echo) return;
            const name = `keyrings.${type}.${Number(id)}`;
            const channel = echo.join(name)
                .here((members) => this.setViewers(type, id, members))
                .joining((member) => this.addViewer(type, id, member))
                .leaving((member) => this.removeViewer(type, id, member))
                .listen('.keyring.changed', (event) => this.applyChanged(type, id, event))
                .listen('.project.editability.changed', (event) => {
                    for (const listener of editabilityListeners) listener(event);
                });
            channels.set(keyFor(type, id), { echo, name, channel });
        },
        leave(type, id) {
            const subscription = channels.get(keyFor(type, id));
            if (!subscription) return;
            channels.delete(keyFor(type, id));
            subscription.echo.leave(subscription.name);
        },
        bindConnection(echo) {
            const connection = echo?.connector?.pusher?.connection;
            if (connectionBound || !connection?.bind) return;
            connectionBound = true;
            let connectedBefore = connection.state === 'connected';
            connection.bind('connected', () => {
                if (connectedBefore) this.reacquireAll();
                connectedBefore = true;
            });
        },
        bindPageLifecycle() {
            if (pageLifecycleBound || typeof window === 'undefined' || !window.addEventListener) return;
            pageLifecycleBound = true;
            window.addEventListener('pagehide', () => this.releaseAllOnPageHide());
            window.addEventListener('pageshow', (event) => {
                if (event.persisted) this.reacquireAll();
            });
        },
        reset() {
            for (const key of channels.keys()) {
                const [type, id] = key.split(':');
                this.leave(type, id);
            }
            this.entries = {};
            editabilityListeners.clear();
            echoFactory = null;
            connectionBound = false;
            pageLifecycleBound = false;
        },
    },
});

/**
 * Reasons that come from the project itself; the keyring part is tracked live by the store.
 */
export function projectReasons(reasons) {
    return (reasons ?? []).filter((reason) => reason !== 'keyring');
}

/**
 * Apply a 423 Locked write response to the keyring store and the reasons list of the loaded payload.
 */
export function rejectNotEditable(response, payload, target) {
    if (response?.status !== 423) return false;
    const reason = useKeyringStore().applyNotEditable(payload);
    if (reason && target) {
        target.reasons = [...new Set([...(target.reasons ?? []), reason])];
        target.read_only = true;
    }

    return true;
}
