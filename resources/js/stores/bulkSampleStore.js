import { defineStore } from 'pinia';
import { useClientSelectorStore } from './clientSelectorStore';
import { useProjectSelectorStore } from './projectSelectorStore';

let previewRequest;
let detailsRequest;

function headers() {
    return { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content };
}

export const useBulkSampleStore = defineStore('bulkSampleRegistration', {
    state: () => ({
        type: '', endpoints: {}, rows: [], profiles: [], samplingMethods: [], defaultProfile: '', defaultSamplingMethod: 0,
        count: 0, form: { sampling_method: 0, sample_note: '' }, clientDetails: null,
        loading: false, submitting: false, previewing: false, error: '', previewError: '', errors: {},
    }),
    getters: {
        legionella: (state) => state.type === 'legionella',
        errorMessages: (state) => Object.values(state.errors).flat(),
        matrices: (state) => state.rows.map((row) => state.type === 'rodac' ? 'normal' : state.profiles.find((profile) => Number(profile.id) === Number(row.profile_id))?.matrix),
    },
    actions: {
        configure(type, endpoints) {
            this.type = type;
            this.endpoints = endpoints;
            useClientSelectorStore().configure(endpoints.clientSearch);
            useProjectSelectorStore().configure(endpoints.clientProjects, endpoints.project, type === 'legionella');
        },
        async initialize() {
            this.loading = true;
            this.error = '';
            try {
                const response = await fetch(this.endpoints.formData, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error();
                const data = await response.json();
                this.profiles = data.profiles;
                this.samplingMethods = data.sampling_methods;
                this.defaultProfile = this.profiles.find((profile) => Number(profile.id) === data.default_profile)?.id ?? this.profiles[0]?.id ?? '';
                this.defaultSamplingMethod = this.samplingMethods.find((method) => Number(method.id) === data.default_sampling_method)?.id ?? this.samplingMethods[0]?.id ?? 0;
                this.form.sampling_method = this.defaultSamplingMethod;
                useProjectSelectorStore().setFieldDefinitions(data.project_fields);
            } catch {
                this.error = 'Het aanmeldformulier kon niet worden geladen.';
            } finally {
                this.loading = false;
            }
        },
        async selectClient(client) {
            useClientSelectorStore().select(client);
            this.errors = {};
            this.clientDetails = null;
            detailsRequest?.abort();
            const request = new AbortController();
            detailsRequest = request;
            await Promise.all([
                useProjectSelectorStore().loadForClient(client.id),
                (async () => {
                    try {
                        const response = await fetch(this.endpoints.clientDetails.replace('__CLIENT__', client.id), { headers: { Accept: 'application/json' }, signal: request.signal });
                        if (!response.ok) throw new Error();
                        this.clientDetails = (await response.json()).data;
                    } catch (error) {
                        if (error.name !== 'AbortError') this.error = 'Klantgegevens konden niet worden opgehaald.';
                    }
                })(),
            ]);
            this.renumberRows();
        },
        clearClient() {
            detailsRequest?.abort();
            useClientSelectorStore().clear();
            useProjectSelectorStore().loadForClient(null);
            this.clientDetails = null;
            this.renumberRows();
        },
        resizeRows(count) {
            const nextCount = Math.max(0, Math.trunc(Number(count) || 0));
            if (nextCount === this.rows.length) return;
            this.count = nextCount;
            this.rows = Array.from({ length: this.count }, (_, index) => this.rows[index] ?? {
                follow: index + 1, profile_id: this.defaultProfile, sampling_method: this.defaultSamplingMethod,
                description: '', location: '', barcode: '',
            });
            this.renumberRows();
        },
        renumberRows() {
            const projectStore = useProjectSelectorStore();
            const project = projectStore.projects.find((item) => String(item.id) === String(projectStore.selectedId));
            this.rows.forEach((row, index) => { row.follow = String(Number(project?.samples_count ?? 0) + index + 1); });
        },
        async refreshBarcodes() {
            previewRequest?.abort();
            this.previewError = '';
            if (!this.rows.length || this.matrices.some((matrix) => !matrix)) {
                this.previewing = false;
                return;
            }
            const request = new AbortController();
            previewRequest = request;
            this.previewing = true;
            try {
                const response = await fetch(this.endpoints.preview, { method: 'POST', headers: headers(), signal: request.signal, body: JSON.stringify({ matrices: this.matrices }) });
                if (!response.ok) throw new Error();
                const data = await response.json();
                this.rows.forEach((row, index) => { row.barcode = data.barcodes[index] ?? ''; });
            } catch (error) {
                if (error.name !== 'AbortError') this.previewError = 'Monsternummers konden niet worden opgehaald.';
            } finally {
                if (previewRequest === request) this.previewing = false;
            }
        },
        async submit() {
            this.error = '';
            this.errors = {};
            const clientStore = useClientSelectorStore();
            const projectStore = useProjectSelectorStore();
            if (!clientStore.selected) {
                this.errors = { client: ['Selecteer een klant uit de zoekresultaten.'] };
                return;
            }
            if (!this.rows.length) {
                this.errors = { samples: ['Voer minimaal een monster in.'] };
                return;
            }
            this.submitting = true;
            try {
                const response = await fetch(this.endpoints.store, {
                    method: 'POST', headers: headers(), body: JSON.stringify({
                        client: clientStore.selected.id, project: projectStore.selectedId || null,
                        project_name: projectStore.form.project_name, project_custom_fields: projectStore.form.custom_fields,
                        ...this.form,
                        samples: this.rows.map((row) => this.legionella
                            ? { follow: row.follow, profile_id: row.profile_id, sampling_method: row.sampling_method }
                            : { follow: row.follow, description: row.description, location: row.location }),
                    }),
                });
                const data = await response.json();
                if (response.status === 422) {
                    this.errors = data.errors ?? {};
                    return;
                }
                if (!response.ok) throw new Error();
                this.resizeRows(0);
                this.form = { sampling_method: this.defaultSamplingMethod, sample_note: '' };
                this.clearClient();
                return data;
            } catch {
                this.error = 'De monsters konden niet worden aangemeld.';
            } finally {
                this.submitting = false;
            }
        },
    },
});