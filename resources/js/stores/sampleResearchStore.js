import { defineStore } from 'pinia';

function entryKey(prefix, counter) {
    return `${prefix}-${counter}`;
}

export const useSampleResearchStore = defineStore('sampleResearch', {
    state: () => ({
        endpoint: '',
        clientId: null,
        profiles: [],
        assays: [],
        matrices: [],
        referenceSources: [],
        selected: [],
        pendingConflict: null,
        loading: false,
        error: '',
        counter: 0,
        locked: false,
        requestId: 0,
    }),
    getters: {
        profileById: (state) => (id) => state.profiles.find((profile) => Number(profile.id) === Number(id)),
        assayById: (state) => (id) => state.assays.find((assay) => Number(assay.id) === Number(id)),
        effectiveAssays: () => (entry) => entry.type === 'assay'
            ? [{ id: entry.assay.id, assay: entry.assay.id, name: entry.assay.name }]
            : entry.profile.assays.filter((assay) => !entry.excludedAssayProfileIds.includes(Number(assay.id))),
        payload() {
            return this.selected.map((entry) => entry.type === 'profile' ? {
                type: 'profile',
                profile_id: entry.profile.id,
                excluded_assay_profile_ids: entry.excludedAssayProfileIds,
            } : {
                type: 'assay',
                assay_id: entry.assay.id,
                settings: entry.settings,
            });
        },
    },
    actions: {
        configure(endpoint) {
            this.endpoint = endpoint;
        },
        async load(clientId) {
            this.reset();
            const requestId = this.requestId;
            if (!clientId) return;
            this.clientId = Number(clientId);
            this.loading = true;
            try {
                const response = await fetch(this.endpoint.replace('__CLIENT__', clientId), {
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) throw new Error();
                const { data } = await response.json();
                if (requestId !== this.requestId) return;
                this.profiles = data.profiles;
                this.assays = data.assays;
                this.matrices = data.matrices;
                this.referenceSources = data.reference_sources;
            } catch {
                if (requestId === this.requestId) this.error = 'De beschikbare onderzoeken konden niet worden geladen.';
            } finally {
                if (requestId === this.requestId) this.loading = false;
            }
        },
        reset() {
            this.requestId++;
            this.loading = false;
            this.clientId = null;
            this.profiles = [];
            this.assays = [];
            this.matrices = [];
            this.referenceSources = [];
            this.selected = [];
            this.pendingConflict = null;
            this.error = '';
            this.counter = 0;
            this.locked = false;
        },
        selectedBaseIds(exceptKey = null) {
            return new Set(this.selected
                .filter((entry) => entry.key !== exceptKey)
                .flatMap((entry) => this.effectiveAssays(entry).map((assay) => Number(assay.assay))));
        },
        requestProfile(profile) {
            this.error = '';
            if (this.selected.some((entry) => entry.type === 'profile' && Number(entry.profile.id) === Number(profile.id))) {
                this.error = 'Dit onderzoeksprofiel is al geselecteerd.';
                return;
            }

            const selectedBaseIds = this.selectedBaseIds();
            const conflicts = profile.assays.filter((assay) => selectedBaseIds.has(Number(assay.assay)));
            if (conflicts.length) {
                this.pendingConflict = { profile, conflicts };
                return;
            }

            this.addProfile(profile);
        },
        resolveConflict(strategy) {
            if (!this.pendingConflict) return;
            const { profile, conflicts } = this.pendingConflict;
            const conflictBaseIds = new Set(conflicts.map((assay) => Number(assay.assay)));

            if (strategy === 'exclude') {
                this.addProfile(profile, conflicts.map((assay) => Number(assay.id)));
            }

            if (strategy === 'replace') {
                this.selected = this.selected.flatMap((entry) => {
                    if (entry.type === 'assay') {
                        return conflictBaseIds.has(Number(entry.assay.id)) ? [] : [entry];
                    }

                    const excluded = entry.profile.assays
                        .filter((assay) => conflictBaseIds.has(Number(assay.assay)))
                        .map((assay) => Number(assay.id));
                    entry.excludedAssayProfileIds = [...new Set([...entry.excludedAssayProfileIds, ...excluded])];
                    return this.effectiveAssays(entry).length ? [entry] : [];
                });
                this.addProfile(profile);
            }

            this.pendingConflict = null;
        },
        addProfile(profile, excludedAssayProfileIds = []) {
            this.selected.push({
                key: entryKey('profile', ++this.counter),
                type: 'profile',
                profile,
                excludedAssayProfileIds,
            });
        },
        addRoaming(assay, settings, editSource = null) {
            this.error = '';
            if (this.selectedBaseIds(editSource?.entryKey).has(Number(assay.id))) {
                this.error = 'Deze analyse is al geselecteerd.';
                return false;
            }

            if (editSource) {
                const profileEntry = this.selected.find((entry) => entry.key === editSource.entryKey);
                if (profileEntry) {
                    profileEntry.excludedAssayProfileIds = [...new Set([
                        ...profileEntry.excludedAssayProfileIds,
                        Number(editSource.assayProfileId),
                    ])];
                }
            }

            this.selected.push({
                key: entryKey('assay', ++this.counter),
                type: 'assay',
                assay,
                settings,
            });
            return true;
        },
        updateRoaming(key, settings) {
            const entry = this.selected.find((item) => item.key === key && item.type === 'assay');
            if (entry) entry.settings = settings;
        },
        remove(key) {
            this.selected = this.selected.filter((entry) => entry.key !== key);
        },
        clearSelections() {
            if (this.locked) return;
            this.selected = [];
            this.pendingConflict = null;
        },
        toggleLock() {
            this.locked = !this.locked;
        },
    },
});
