import { defineStore } from 'pinia';

let activeSearchRequest;
let activeProjectRequest;
let activeSampleRequest;
const analysisResultRevisions = new Map();

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
        projectActionLoading: false, projectActionError: '', deletingSample: false,
        searched: false, error: '', sampleError: '',
        savingVeto: false, vetoError: '',
    }),
    getters: {
        selectedSample: (state) => state.projectData?.samples.find(
            (sample) => Number(sample.id) === Number(state.selectedSampleId),
        ) ?? null,
        sampleReadOnly: (state) => state.projectActionLoading || (state.projectData?.project
            ? Boolean(Number(state.projectData.project.auth_status) || Number(state.projectData.project.locked))
            : Boolean(state.sampleData?.read_only)),
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
            window.history.replaceState({}, '', this.endpoints.page);

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
            this.projectActionError = '';
            this.clearSample();
            this.loadingProject = true;
            this.error = '';

            try {
                const response = await fetch(this.endpoints.project.replace('__PROJECT__', projectId), {
                    headers: { Accept: 'application/json' },
                    signal: request.signal,
                });
                this.projectData = await responseData(response);
                window.history.replaceState({}, '', this.projectData.url);
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

                await this.refreshAnalysisResults();
                return true;
            } catch (error) {
                if (error.name === 'AbortError') return null;
                this.sampleError = error.message;
                return false;
            } finally {
                if (activeSampleRequest === request) this.loadingSample = false;
            }
        },
        async deleteSample() {
            if (!this.sampleData || this.sampleReadOnly || this.deletingSample) return false;

            const projectId = this.selectedProjectId;
            const sampleId = this.selectedSampleId;
            this.deletingSample = true;
            this.sampleError = '';
            try {
                const response = await fetch(this.sampleEndpoint(sampleId), {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                });
                await responseData(response);
                if (this.selectedProjectId === projectId) await this.selectProject(projectId);
                return true;
            } catch (error) {
                this.sampleError = error.message;
                return false;
            } finally {
                this.deletingSample = false;
            }
        },
        async refreshAnalysisResults() {
            await Promise.all((this.sampleData?.analyses ?? []).map((analysis) => this.refreshAnalysisResult(analysis.id)));
        },
        async refreshAnalysisResult(analysisId) {
            if (!this.sampleData?.analyses.some((analysis) => Number(analysis.id) === Number(analysisId))) return;

            const request = activeSampleRequest;
            const projectId = this.selectedProjectId;
            const sampleId = this.selectedSampleId;
            const revision = (analysisResultRevisions.get(analysisId) ?? 0) + 1;
            analysisResultRevisions.set(analysisId, revision);
            const isCurrent = () => activeSampleRequest === request
                && this.selectedProjectId === projectId && this.selectedSampleId === sampleId
                && analysisResultRevisions.get(analysisId) === revision;

            try {
                const response = await fetch(this.endpoints.results
                    .replace('__PROJECT__', projectId)
                    .replace('__SAMPLE__', sampleId)
                    .replace('__ANALYSIS__', analysisId), {
                    headers: { Accept: 'application/json' },
                    signal: request?.signal,
                });
                const data = await responseData(response);
                if (isCurrent()) this.applyAnalysisResult(analysisId, data);
            } catch (error) {
                if (error.name !== 'AbortError' && isCurrent()) {
                    this.analysisResults[analysisId] = { data: null, error: error.message };
                }
            }
        },
        applyAnalysisResult(analysisId, data) {
            this.analysisResults[analysisId] = { data, error: '' };
            const analysis = this.sampleData?.analyses.find((item) => Number(item.id) === Number(analysisId));
            if (analysis) Object.assign(analysis, data.analysis);
        },
        applyCalculationFailure(event) {
            if (Number(event.sample_id) !== Number(this.selectedSampleId)) return;
            const analysisId = Number(event.analysis_id);
            analysisResultRevisions.set(analysisId, (analysisResultRevisions.get(analysisId) ?? 0) + 1);
            this.analysisResults[analysisId] = { data: null, error: event.message || 'Eindresultaat kon niet worden berekend.' };
        },
        reportRealtimeError() {
            for (const result of Object.values(this.analysisResults)) {
                if (result.data?.calculation_queued) result.error = 'Live updates zijn niet beschikbaar. Selecteer het monster opnieuw.';
            }
        },
        async changeVeto(analysisId, payload, remove = false) {
            if (!this.sampleData || this.sampleReadOnly || this.savingVeto) return false;

            const projectId = this.selectedProjectId;
            const sampleId = this.selectedSampleId;
            const sampleRequest = activeSampleRequest;
            this.savingVeto = true;
            this.vetoError = '';

            try {
                const response = await fetch(this.endpoints.veto
                    .replace('__PROJECT__', projectId)
                    .replace('__SAMPLE__', sampleId)
                    .replace('__ANALYSIS__', analysisId), {
                    method: remove ? 'DELETE' : 'PUT',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify(payload),
                });
                const data = await responseData(response);

                if (this.selectedProjectId === projectId && this.selectedSampleId === sampleId
                    && activeSampleRequest === sampleRequest) {
                    analysisResultRevisions.set(analysisId, (analysisResultRevisions.get(analysisId) ?? 0) + 1);
                    this.applyAnalysisResult(analysisId, data);
                    await this.refreshProject(projectId);
                }

                return true;
            } catch (error) {
                if (this.selectedProjectId === projectId && this.selectedSampleId === sampleId) {
                    this.vetoError = error.message;
                }
                return false;
            } finally {
                this.savingVeto = false;
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
        async authorizeProject({ quiet = false, overrideIncomplete = false } = {}) {
            return this.updateProjectAuthorization('POST', {
                quiet,
                override_incomplete: overrideIncomplete,
            });
        },
        async deauthorizeProject({ origin, reason }) {
            return this.updateProjectAuthorization('DELETE', { origin, reason });
        },
        async updateProjectAuthorization(method, payload) {
            if (this.selectedProjectId === null || !this.projectData || this.projectActionLoading) return null;

            const projectId = this.selectedProjectId;
            this.projectActionLoading = true;
            this.projectActionError = '';

            try {
                const response = await fetch(this.endpoints.authorization.replace('__PROJECT__', projectId), {
                    method,
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify(payload),
                });
                const result = await responseData(response);
                if (Number(this.selectedProjectId) === Number(projectId) && this.projectData?.project && result.project) {
                    Object.assign(this.projectData.project, result.project);

                    if (result.authorized || result.deauthorized) {
                        await this.refreshProject(projectId);

                        if (Number(this.selectedProjectId) === Number(projectId) && this.selectedSampleId !== null) {
                            if (this.selectedSample) {
                                await this.selectSample(this.selectedSampleId);
                            } else {
                                activeSampleRequest?.abort();
                                this.selectedSampleId = null;
                                this.clearSample();
                            }
                        }
                    }
                }

                return result;
            } catch (error) {
                this.projectActionError = error.message;
                return null;
            } finally {
                this.projectActionLoading = false;
            }
        },
        async refreshProject(projectId) {
            activeProjectRequest?.abort();
            const request = new AbortController();
            activeProjectRequest = request;

            try {
                const response = await fetch(this.endpoints.project.replace('__PROJECT__', projectId), {
                    headers: { Accept: 'application/json' },
                    signal: request.signal,
                });
                const data = await responseData(response);

                if (activeProjectRequest === request && Number(this.selectedProjectId) === Number(projectId)) {
                    this.projectData = data;
                }
            } catch (error) {
                if (error.name !== 'AbortError' && Number(this.selectedProjectId) === Number(projectId)) {
                    this.projectActionError = `De projectstatus is gewijzigd, maar de projectgegevens konden niet worden vernieuwd: ${error.message}`;
                }
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
            this.projectActionError = '';
            this.clearSample();
            if (this.mode === 'barcode') {
                this.projects = [];
                this.searched = false;
            }
        },
    },
});