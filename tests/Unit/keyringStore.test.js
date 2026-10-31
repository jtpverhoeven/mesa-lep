import { afterEach, beforeEach, test } from 'node:test';
import assert from 'node:assert/strict';
import { createPinia, setActivePinia } from 'pinia';
import { rejectNotEditable, useKeyringStore } from '../../resources/js/stores/keyringStore.js';

const originalFetch = globalThis.fetch;
const originalNavigator = Object.getOwnPropertyDescriptor(globalThis, 'navigator');
let requests;
let listeners;

function held(overrides = {}) {
    return { locked: false, lock_placed: true, lock_id: 40, locked_by_id: 1, locked_by_name: 'Ik', locked_by_avatar: null, expires_at: null, ...overrides };
}

function heldByOther(overrides = {}) {
    return { locked: true, lock_placed: false, lock_id: 41, locked_by_id: 2, locked_by_name: 'Bea', locked_by_avatar: '/avatars/2', expires_at: null, ...overrides };
}

function respondWith(status) {
    globalThis.fetch = async (url, options = {}) => {
        requests.push({ url, method: options.method ?? 'GET', options });
        return { ok: true, json: async () => ({ data: status }) };
    };
}

function fakeEcho() {
    const channel = { handlers: {}, listeners: {} };
    for (const name of ['here', 'joining', 'leaving']) {
        channel[name] = (callback) => { channel.handlers[name] = callback; return channel; };
    }
    channel.listen = (event, callback) => { channel.listeners[event] = callback; return channel; };
    const connection = { state: 'connected', handlers: {}, bind(event, callback) { this.handlers[event] = callback; } };
    const echo = {
        joined: [],
        left: [],
        channel,
        connector: { pusher: { connection } },
        join(name) { this.joined.push(name); return channel; },
        leave(name) { this.left.push(name); },
    };

    return echo;
}

async function settle() {
    await new Promise((resolve) => setTimeout(resolve, 520));
}

beforeEach(() => {
    setActivePinia(createPinia());
    requests = [];
    listeners = {};
    globalThis.document = { querySelector: () => ({ content: 'csrf-token' }) };
    globalThis.window = { addEventListener: (event, callback) => { listeners[event] = callback; } };
});

afterEach(() => {
    useKeyringStore().reset();
    globalThis.fetch = originalFetch;
    delete globalThis.document;
    delete globalThis.window;
    if (originalNavigator) Object.defineProperty(globalThis, 'navigator', originalNavigator);
});

test('acquiring joins the presence channel and claims the keyring', async () => {
    const store = useKeyringStore();
    const echo = fakeEcho();
    store.configure({ base_url: '/keyrings/', user_id: 1, can_steal: false }, () => echo);
    respondWith(held());

    const status = await store.acquire('sample', '5', heldByOther());

    assert.deepEqual(echo.joined, ['keyrings.sample.5']);
    assert.equal(requests[0].url, '/keyrings/sample/5');
    assert.equal(requests[0].method, 'POST');
    assert.equal(requests[0].options.headers['X-CSRF-TOKEN'], 'csrf-token');
    assert.equal(status.lock_placed, true);
    assert.equal(store.isLockedByOther('sample', 5), false);
});

test('the initial status keeps the page read-only until the claim answers', async () => {
    const store = useKeyringStore();
    store.configure({ user_id: 1 });
    let answer;
    globalThis.fetch = () => new Promise((resolve) => { answer = resolve; });

    const pending = store.acquire('project', 9, heldByOther());

    assert.equal(store.isLockedByOther('project', 9), true);
    assert.deepEqual(store.holder('project', 9), { id: 2, name: 'Bea', avatar_url: '/avatars/2' });
    answer({ ok: true, json: async () => ({ data: heldByOther() }) });
    await pending;
    assert.equal(store.isLockedByOther('project', 9), true);
});

test('releasing leaves the channel and only deletes keyrings this page holds', async () => {
    const store = useKeyringStore();
    const echo = fakeEcho();
    store.configure({ user_id: 1 }, () => echo);
    respondWith(heldByOther());
    await store.acquire('sample', 5);
    requests.length = 0;

    await store.release('sample', 5);

    assert.deepEqual(echo.left, ['keyrings.sample.5']);
    assert.deepEqual(requests, []);
    assert.equal(store.entry('sample', 5), null);

    respondWith(held());
    await store.acquire('sample', 6);
    requests.length = 0;
    await store.release('sample', 6);

    assert.equal(requests[0].url, '/keyrings/sample/6');
    assert.equal(requests[0].method, 'DELETE');
    assert.equal(requests[0].options.keepalive, true);
});

test('stealing requires the permission and clears the stolen notice', async () => {
    const store = useKeyringStore();
    store.configure({ user_id: 1, can_steal: false });
    respondWith(heldByOther());
    await store.acquire('sample', 5);
    requests.length = 0;

    assert.equal(await store.steal('sample', 5), null);
    assert.deepEqual(requests, []);

    store.config.can_steal = true;
    store.entry('sample', 5).stolenBy = 'Bea';
    respondWith(held());
    const status = await store.steal('sample', 5);

    assert.equal(requests[0].url, '/keyrings/sample/5/steal');
    assert.equal(requests[0].method, 'POST');
    assert.equal(status.lock_placed, true);
    assert.equal(store.entry('sample', 5).stolenBy, null);
    assert.equal(store.isLockedByOther('sample', 5), false);
});

test('failed requests keep the previous status and expose the error', async () => {
    const store = useKeyringStore();
    store.configure({ user_id: 1 });
    globalThis.fetch = async () => ({ ok: false, status: 500, json: async () => ({ message: 'Serverfout' }) });

    assert.equal(await store.acquire('sample', 5, heldByOther()), null);

    assert.equal(store.entry('sample', 5).error, 'Serverfout');
    assert.equal(store.isLockedByOther('sample', 5), true);
    assert.equal(store.entry('sample', 5).pending, false);
});

test('a steal broadcast makes the victim read-only and names the new holder', async () => {
    const store = useKeyringStore();
    const echo = fakeEcho();
    store.configure({ user_id: 1 }, () => echo);
    respondWith(held());
    await store.acquire('sample', 5);

    echo.channel.listeners['.keyring.changed']({
        type: 'sample', type_id: 5, reason: 'stolen', previous_holder_id: 1,
        holder: { holder_id: 2, name: 'Bea', avatar_url: '/avatars/2', lock_id: 41, expires_at: null },
    });

    assert.equal(store.isLockedByOther('sample', 5), true);
    assert.equal(store.entry('sample', 5).stolenBy, 'Bea');
    assert.equal(store.entry('sample', 5).status.lock_placed, false);
});

test('a holder change that is not a steal of this user shows no stolen notice', async () => {
    const store = useKeyringStore();
    store.configure({ user_id: 1 });
    respondWith(heldByOther());
    await store.acquire('sample', 5);

    store.applyChanged('sample', 5, { reason: 'stolen', previous_holder_id: 3, holder: { holder_id: 2, name: 'Bea', lock_id: 41 } });

    assert.equal(store.isLockedByOther('sample', 5), true);
    assert.equal(store.entry('sample', 5).stolenBy, null);
});

test('a released broadcast frees the keyring and re-acquires it after a jitter', async () => {
    const store = useKeyringStore();
    store.configure({ user_id: 1 });
    respondWith(heldByOther());
    await store.acquire('sample', 5);
    requests.length = 0;
    respondWith(held());

    store.applyChanged('sample', 5, { reason: 'released', holder: null, previous_holder_id: 2 });

    assert.equal(store.isLockedByOther('sample', 5), false);
    assert.deepEqual(requests, []);
    await settle();
    assert.equal(requests.length, 1);
    assert.equal(requests[0].url, '/keyrings/sample/5');
    assert.equal(store.entry('sample', 5).status.lock_placed, true);
});

test('presence tracks other viewers and re-acquires when the holder leaves', async () => {
    const store = useKeyringStore();
    const echo = fakeEcho();
    store.configure({ user_id: 1 }, () => echo);
    respondWith(heldByOther());
    await store.acquire('sample', 5);
    requests.length = 0;

    echo.channel.handlers.here([{ id: 1, name: 'Ik' }, { id: 2, name: 'Bea' }]);
    echo.channel.handlers.joining({ id: 3, name: 'Cas' });
    assert.deepEqual(store.viewers('sample', 5).map((viewer) => viewer.id), [2, 3]);

    echo.channel.handlers.leaving({ id: 3, name: 'Cas' });
    await settle();
    assert.deepEqual(requests, []);

    respondWith(held());
    echo.channel.handlers.leaving({ id: 2, name: 'Bea' });
    await settle();

    assert.deepEqual(store.viewers('sample', 5), []);
    assert.equal(requests.length, 1);
    assert.equal(store.isLockedByOther('sample', 5), false);
});

test('every Echo reconnect re-acquires all keyrings', async () => {
    const store = useKeyringStore();
    const echo = fakeEcho();
    store.configure({ user_id: 1 }, () => echo);
    respondWith(held());
    await store.acquire('sample', 5);
    await store.acquire('project', 9);
    requests.length = 0;

    echo.connector.pusher.connection.handlers.connected();

    await new Promise((resolve) => setTimeout(resolve, 0));
    assert.deepEqual(requests.map((request) => request.url).sort(), ['/keyrings/project/9', '/keyrings/sample/5']);
});

test('pagehide releases held keyrings with a beacon and pageshow from the cache re-acquires them', async () => {
    const store = useKeyringStore();
    const beacons = [];
    Object.defineProperty(globalThis, 'navigator', { configurable: true, value: { sendBeacon: (url, body) => { beacons.push({ url, token: body.get('_token') }); return true; } } });
    store.configure({ base_url: '/keyrings', user_id: 1 });
    respondWith(held({ lock_id: 40 }));
    await store.acquire('sample', 5);
    respondWith(heldByOther());
    await store.acquire('sample', 6);
    requests.length = 0;

    listeners.pagehide();

    assert.deepEqual(beacons, [{ url: '/keyrings/40/release', token: 'csrf-token' }]);
    assert.deepEqual(requests, []);
    assert.equal(store.entry('sample', 5).status.lock_placed, false);

    respondWith(held());
    listeners.pageshow({ persisted: false });
    assert.deepEqual(requests, []);
    listeners.pageshow({ persisted: true });
    await new Promise((resolve) => setTimeout(resolve, 0));
    assert.equal(requests.length, 2);
});

test('pagehide falls back to a keepalive delete when beacons are unavailable', async () => {
    const store = useKeyringStore();
    Object.defineProperty(globalThis, 'navigator', { configurable: true, value: {} });
    store.configure({ user_id: 1 });
    respondWith(held());
    await store.acquire('export', 9);
    requests.length = 0;

    listeners.pagehide();

    assert.equal(requests[0].url, '/keyrings/export/9');
    assert.equal(requests[0].method, 'DELETE');
    assert.equal(requests[0].options.keepalive, true);
});

test('a 423 keyring response marks the resource as stolen from this page', async () => {
    const store = useKeyringStore();
    store.configure({ user_id: 1 });
    respondWith(held());
    await store.acquire('sample', 5);
    const target = { reasons: [] };

    const handled = rejectNotEditable({ status: 423 }, { reason: 'keyring', keyring: { type: 'sample', type_id: 5, ...heldByOther() } }, target);

    assert.equal(handled, true);
    assert.equal(store.isLockedByOther('sample', 5), true);
    assert.equal(store.entry('sample', 5).stolenBy, 'Bea');
    assert.deepEqual(target.reasons, []);
});

test('a 423 project response adds the reason and other statuses are ignored', () => {
    const store = useKeyringStore();
    const target = { reasons: ['keyring'], read_only: false };

    assert.equal(rejectNotEditable({ status: 403 }, { reason: 'authorized' }, target), false);
    assert.deepEqual(target.reasons, ['keyring']);

    assert.equal(rejectNotEditable({ status: 423 }, { reason: 'authorized', keyring: null }, target), true);
    assert.deepEqual(target.reasons, ['keyring', 'authorized']);
    assert.equal(target.read_only, true);
    assert.equal(store.isReadOnly(target.reasons, 'sample', 5), true);
    assert.equal(store.isReadOnly(['keyring'], 'sample', 5), false);
});

test('project editability broadcasts reach registered listeners until they unsubscribe', async () => {
    const store = useKeyringStore();
    const echo = fakeEcho();
    store.configure({ user_id: 1 }, () => echo);
    respondWith(held());
    await store.acquire('project', 9);
    const events = [];
    const stop = store.onEditabilityChanged((event) => events.push(event));

    echo.channel.listeners['.project.editability.changed']({ project_id: 9, read_only: true, reasons: ['authorization_blocked'] });
    stop();
    echo.channel.listeners['.project.editability.changed']({ project_id: 9, read_only: false, reasons: [] });

    assert.deepEqual(events, [{ project_id: 9, read_only: true, reasons: ['authorization_blocked'] }]);
});
