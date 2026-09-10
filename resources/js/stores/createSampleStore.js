import { defineStore } from 'pinia';
import { useClientSelectorStore } from './clientSelectorStore';
import { legacyFieldValues } from './legacyFieldDefaults';
import { useProjectSelectorStore } from './projectSelectorStore';
import { useSampleResearchStore } from './sampleResearchStore';

function emptyForm() {
    return { description: '', sampling_method: '', sample_note: '', custom_fields: {} };
}

export const useCreateSampleStore = defineStore('createSample', {
    state: () => ({
        endpoints: {},
        form: emptyForm(),
        barcode: '',
        sampleFields: [],
        samplingMethods: [],
        loading: false,
        submitting: false,
        error: '',
        errors: {},
    }),
    getters: {
        errorMessages: (state) => Object.values(state.errors).flat(),
    },
    actions: {
        configure(endpoints) {
            this.endpoints = endpoints;
            useClientSelectorStore().configure(endpoints.clientSearch);
            useProjectSelectorStore().configure(endpoints.clientProjects, endpoints.project);
            useSampleResearchStore().configure(endpoints.analysisOptions);
        },
        async initialize() {
            this.loading = true;
            this.error = '';
            try {
                const response = await fetch(this.endpoints.formData, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error();
                const data = await response.json();
                this.barcode = data.barcode;
                this.sampleFields = data.sample_fields;
                this.samplingMethods = data.sampling_methods;
                this.form.custom_fields = legacyFieldValues(this.sampleFields);
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
            await Promise.all([
                useProjectSelectorStore().loadForClient(client.id),
                useSampleResearchStore().load(client.id),
            ]);
        },
        clearClient() {
            useClientSelectorStore().clear();
            useProjectSelectorStore().loadForClient(null);
            useSampleResearchStore().reset();
        },
        async submit() {
            const clientStore = useClientSelectorStore();
            const projectStore = useProjectSelectorStore();
            this.error = '';
            this.errors = {};

            if (!clientStore.selected) {
                this.errors = { client: ['Selecteer een klant uit de zoekresultaten.'] };
                return;
            }

            this.submitting = true;
            try {
                const response = await fetch(this.endpoints.store, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        client: clientStore.selected.id,
                        project: projectStore.selectedId || null,
                        project_name: projectStore.form.project_name,
                        project_custom_fields: projectStore.form.custom_fields,
                        analyses: useSampleResearchStore().payload,
                        ...this.form,
                    }),
                });
                const data = await response.json();
                if (response.status === 422) {
                    this.errors = data.errors ?? {};
                    return;
                }
                if (!response.ok) throw new Error();

                this.barcode = data.next_barcode;
                this.form = { ...emptyForm(), custom_fields: legacyFieldValues(this.sampleFields) };
                useSampleResearchStore().clearSelections();
                await projectStore.loadForClient(clientStore.selected.id);
                await projectStore.selectProject(String(data.sample.project_id), clientStore.selected.id);
                projectStore.form.project_name = data.sample.project_name;
                return data.sample;
            } catch {
                this.error = 'Het monster kon niet worden aangemeld.';
            } finally {
                this.submitting = false;
            }

            return null;
        },
    },
});