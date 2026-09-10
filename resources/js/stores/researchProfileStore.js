import { defineStore } from 'pinia';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function request(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...options.headers,
        },
    });
    const body = await response.json().catch(() => ({}));
    if (!response.ok) {
        const message = Object.values(body.errors ?? {}).flat()[0] ?? body.message ?? 'De aanvraag kon niet worden verwerkt.';
        throw new Error(message);
    }

    return body;
}

export const useResearchProfileStore = defineStore('researchProfiles', {
    state: () => ({
        endpoints: {}, profiles: [], profile: null, revisions: [], matrices: [], assays: [], referenceSources: [],
        loading: false, saving: false, error: '', message: '', query: '',
    }),
    getters: {
        assayById: (state) => (id) => state.assays.find((assay) => Number(assay.id) === Number(id)),
    },
    actions: {
        configure(endpoints) {
            this.endpoints = endpoints;
        },
        async loadProfiles(query = '') {
            this.loading = true;
            this.error = '';
            this.query = query;
            try {
                const url = new URL(this.endpoints.list, window.location.origin);
                if (query.trim()) url.searchParams.set('query', query.trim());
                this.profiles = (await request(url)).data;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },
        async loadEditor() {
            this.loading = true;
            this.error = '';
            try {
                const data = (await request(this.endpoints.data)).data;
                this.profile = data.profile;
                this.revisions = data.revisions;
                this.matrices = data.matrices;
                this.assays = data.assays;
                this.referenceSources = data.reference_sources ?? [];
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },
        async loadReferenceSources(client = null) {
            if (!this.endpoints.referenceSources) return;
            try {
                const url = new URL(this.endpoints.referenceSources, window.location.origin);
                if (client) url.searchParams.set('client', client);
                this.referenceSources = (await request(url)).data;
            } catch (error) {
                this.error = error.message;
            }
        },
        async save(payload) {
            this.saving = true;
            this.error = '';
            try {
                const body = await request(this.endpoints.save, { method: this.profile ? 'PUT' : 'POST', body: JSON.stringify(payload) });
                window.location.href = this.endpoints.edit.replace('__ID__', body.data.id);
            } catch (error) {
                this.error = error.message;
            } finally {
                this.saving = false;
            }
        },
        async remove(id) {
            if (!window.confirm('Onderzoeksprofiel verwijderen?')) return;
            try {
                const body = await request(this.endpoints.remove.replace('__ID__', id), { method: 'DELETE' });
                this.message = body.message;
                await this.loadProfiles(this.query);
            } catch (error) {
                this.error = error.message;
            }
        },
        async copy(sourceId) {
            this.saving = true;
            this.error = '';
            try {
                const body = await request(this.endpoints.copy, { method: 'POST', body: JSON.stringify({ source_id: sourceId }) });
                this.message = body.message;
                await this.loadEditor();
            } catch (error) {
                this.error = error.message;
            } finally {
                this.saving = false;
            }
        },
        async bulk(payload) {
            this.saving = true;
            this.error = '';
            try {
                const body = await request(this.endpoints.bulk, { method: 'POST', body: JSON.stringify(payload) });
                this.message = body.message;
                return true;
            } catch (error) {
                this.error = error.message;
                return false;
            } finally {
                this.saving = false;
            }
        },
    },
});