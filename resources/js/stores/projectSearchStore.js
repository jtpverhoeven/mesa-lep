import { defineStore } from 'pinia';

let activeSearchRequest;
let activeProjectRequest;
let activeSampleRequest;

async function responseData(response) {
    const payload = await response.json();
    if (!response.ok) throw new Error(payload.message || 'De aanvraag kon niet worden verwerkt.');
    return payload.data;
}

export const useProjectSearchStore = defineStore('projectSearch', {
    state: () => ({
        endpoints: {}, mode: 'barcode', query: '', projects: [], projectData: null,
        selectedProjectId: null, selectedSampleId: null, searching: false, loadingProject: false,
        sampleData: null, analysisResults: {}, loadingSample: false, savingSample: {},
        searched: false, error: '', sampleError: '',
    }),
    getters: {
        selectedSample: (state) => state.projectData?.samples.find(
            (sample) => Number(sample.id) === Number(state.selectedSampleId),
        ) ?? null,
        sampleReadOnly: (state) => Boolean(state.sampleData?.read_only),
        showsResults: (state) => state.mode === 'reference'
            && (state.searching || state.searched)
            && state.selectedProjectId === null,
    },
    actions: {
        configure(endpoints) {
            this.endpoints = endpoints;
        },
        setMode(mode) {
            this.mode = mode;
            this.query = '';
            this.error = '';
        },
        async search() {
            const query = this.query.trim();
            if (!query || this.searching) return null;

            activeSearchRequest?.abort();
            activeProjectRequest?.abort();
            activeSampleRequest?.abort();
            const request = new AbortController();
            activeSearchRequest = request;
            this.searching = true;
            this.searched = false;
            this.error = '';
            this.projects = [];
            this.projectData = null;
            this.selectedProjectId = null;
            this.selectedSampleId = null;
            this.clearSample();

            try {
                const parameters = new URLSearchParams({ mode: this.mode, query });
                const response = await fetch(`${this.endpoints.search}?${parameters}`, {
                    headers: { Accept: 'application/json' },
                    signal: request.signal,
                });
                const data = await responseData(response);
                this.projects = data.projects;
                this.searched = true;

                if (data.selected_project_id) return await this.selectProject(data.selected_project_id, data.selected_sample_id);
                return this.mode === 'barcode' ? false : true;
            } catch (error) {
                if (error.name === 'AbortError') return null;
                this.error = error.message;
                return false;
            } finally {
                if (activeSearchRequest === request) this.searching = false;
            }
        },
        async selectProject(projectId, sampleId = null) {
            activeProjectRequest?.abort();
            activeSampleRequest?.abort();
            const request = new AbortController();
            activeProjectRequest = request;
            this.selectedProjectId = Number(projectId);
            this.selectedSampleId = sampleId ? Number(sampleId) : null;
            this.projectData = null;
            this.clearSample();
            this.loadingProject = true;
            this.error = '';

            try {
                const response = await fetch(this.endpoints.project.replace('__PROJECT__', projectId), {
                    headers: { Accept: 'application/json' },
                    signal: request.signal,
                });
                this.projectData = await responseData(response);
                if (sampleId && !this.selectedSample) this.selectedSampleId = null;
                if (this.selectedSampleId) await this.selectSample(this.selectedSampleId);
                return true;
            } catch (error) {
                if (error.name === 'AbortError') return null;
                this.error = error.message;
                this.selectedProjectId = null;
                return false;
            } finally {
                if (activeProjectRequest === request) this.loadingProject = false;
            }
        },
        async selectSample(sampleId) {
            activeSampleRequest?.abort();
            const request = new AbortController();
            activeSampleRequest = request;
            this.selectedSampleId = Number(sampleId);
            this.sampleData = null;
            this.analysisResults = {};
            this.loadingSample = true;
            this.sampleError = '';

            try {
                const endpoint = this.sampleEndpoint(sampleId);
                const response = await fetch(endpoint, {
                    headers: { Accept: 'application/json' },
                    signal: request.signal,
                });
                const data = await responseData(response);
                if (activeSampleRequest !== request) return null;
                this.sampleData = data;

                const outcomes = await Promise.all(data.analyses.map(async (analysis) => {
                    try {
                        const resultResponse = await fetch(this.endpoints.results
                            .replace('__PROJECT__', this.selectedProjectId)
                            .replace('__SAMPLE__', this.selectedSampleId)
                            .replace('__ANALYSIS__', analysis.id), {
                            headers: { Accept: 'application/json' },
                            signal: request.signal,
                        });
                        return [analysis.id, { data: await responseData(resultResponse), error: '' }];
                    } catch (error) {
                        if (error.name === 'AbortError') throw error;
                        return [analysis.id, { data: null, error: error.message }];
                    }
                }));
                if (activeSampleRequest === request) this.analysisResults = Object.fromEntries(outcomes);
                return true;
            } catch (error) {
                if (error.name === 'AbortError') return null;
                this.sampleError = error.message;
                return false;
            } finally {
                if (activeSampleRequest === request) this.loadingSample = false;
            }
        },
        async updateSampleField(source, field, value) {
            if (!this.sampleData || this.sampleReadOnly) return false;

            const key = `${source}:${field}`;
            this.savingSample[key] = true;
            this.sampleError = '';

            try {
                const response = await fetch(this.sampleEndpoint(this.selectedSampleId), {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({ source, field, value: String(value ?? '') }),
                });
                const data = await responseData(response);
                this.sampleData = data;
                const summary = this.projectData?.samples.find((sample) => Number(sample.id) === Number(this.selectedSampleId));
                if (summary) summary.description = data.sample.description;
                return true;
            } catch (error) {
                this.sampleError = error.message;
                return false;
            } finally {
                delete this.savingSample[key];
            }
        },
        sampleEndpoint(sampleId) {
            return this.endpoints.sample
                .replace('__PROJECT__', this.selectedProjectId)
                .replace('__SAMPLE__', sampleId);
        },
        clearSample() {
            this.sampleData = null;
            this.analysisResults = {};
            this.loadingSample = false;
            this.savingSample = {};
            this.sampleError = '';
        },
        backToResults() {
            activeProjectRequest?.abort();
            activeSampleRequest?.abort();
            this.projectData = null;
            this.selectedProjectId = null;
            this.selectedSampleId = null;
            this.loadingProject = false;
            this.clearSample();
            if (this.mode === 'barcode') {
                this.projects = [];
                this.searched = false;
            }
        },
    },
});