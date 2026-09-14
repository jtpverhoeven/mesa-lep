import { defineStore } from 'pinia';
import { getEcho } from '../echo.js';

const mutationQueues = new Map();

function socketId() {
    try {
        return getEcho().socketId();
    } catch {
        return null;
    }
}

export const useConfirmationStore = defineStore('confirmation', {
    state: () => ({
        endpoints: {},
        sampleId: null,
        analysisId: null,
        data: null,
        selectedScopeKey: null,
        dialogOpen: false,
        loading: false,
        mutations: {},
        pendingMutationCount: 0,
        pendingRealtimeRefresh: false,
        lastEventRevision: null,
        error: '',
        decisionPrompt: null,
        requestRevision: 0,
    }),
    getters: {
        readOnly: (state) => Boolean(state.data?.read_only),
        selectedScope: (state) => state.data?.scopes?.find((scope) => `${scope.df}:${scope.rep}` === state.selectedScopeKey) ?? state.data?.selected_scope ?? null,
        selectedEvaluation: (state) => state.data ? {
            contenders: state.data.contenders ?? [],
            metadataFields: state.data.metadata_fields ?? [],
            summary: state.data.summary ?? {},
        } : null,
        pendingDecision: (state) => Boolean(state.data?.decision_required),
    },
    actions: {
        configure(endpoints) {
            this.endpoints = endpoints ?? {};
        },
        setSample(sampleId) {
            this.sampleId = sampleId ? Number(sampleId) : null;
        },
        hydrate(analysisId, data) {
            if (!data || Number(analysisId) !== Number(this.analysisId ?? analysisId)) return;
            this.analysisId = Number(analysisId);
            this.applyData(data);
        },
        applyData(data) {
            this.data = data;
            const scope = data?.selected_scope ?? data?.scopes?.[0];
            this.selectedScopeKey = scope ? `${scope.df}:${scope.rep}` : null;
            this.error = '';
            if (data?.decision_required && !this.decisionPrompt) {
                this.decisionPrompt = { analysis_id: data.analysis_id };
            }
        },
        endpoint(name) {
            return String(this.endpoints[name] ?? '').replace('__ANALYSIS__', this.analysisId);
        },
        async load(analysisId = this.analysisId, df = null, rep = 0) {
            if (!analysisId || !this.endpoints.show) return;
            const revision = ++this.requestRevision;
            this.analysisId = Number(analysisId);
            this.loading = true;
            this.error = '';
            const query = df === null ? '' : `?${new URLSearchParams({ df, rep: String(rep) })}`;

            try {
                const response = await fetch(`${this.endpoint('show')}${query}`, { headers: { Accept: 'application/json' } });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Bevestiging kon niet worden geladen.');
                if (revision !== this.requestRevision || Number(analysisId) !== Number(this.analysisId)) return;
                this.applyData(result.data);
            } catch (error) {
                if (revision === this.requestRevision) this.error = error.message;
            } finally {
                if (revision === this.requestRevision) this.loading = false;
            }
        },
        async open(analysisId, df = null, rep = 0) {
            this.dialogOpen = true;
            await this.load(analysisId, df, rep);
        },
        close() {
            this.dialogOpen = false;
        },
        async setDecision(decision) {
            const result = await this.mutate('decision', this.endpoint('decision'), { decision });
            if (result) this.decisionPrompt = null;
            return result;
        },
        async saveTrackAnswer(contender, step, value) {
            const scope = this.selectedScope;
            return this.mutate(`track:${contender}:${step}`, this.endpoint('track'), { df: scope.df, rep: scope.rep, contender, step, value });
        },
        async saveMetadata(key, value) {
            const scope = this.selectedScope;
            return this.mutate(`metadata:${key}`, this.endpoint('metadata'), { df: scope.df, rep: scope.rep, key, value });
        },
        async toggleSupport(mediaId, active) {
            const scope = this.selectedScope;
            return this.mutate(`support:${mediaId}`, this.endpoint('support'), { df: scope.df, rep: scope.rep, media_id: mediaId, active });
        },
        async addContender() {
            const scope = this.selectedScope;
            return this.mutate('contender:add', this.endpoint('contenders'), { df: scope.df, rep: scope.rep }, 'POST');
        },
        async removeContender(index) {
            const scope = this.selectedScope;
            return this.mutate(`contender:remove:${index}`, this.endpoint('contender').replace('__CONTENDER__', index), { df: scope.df, rep: scope.rep }, 'DELETE');
        },
        async saveNote(note) {
            const scope = this.selectedScope;
            return this.mutate('note', this.endpoint('note'), { df: scope.df, rep: scope.rep, note });
        },
        async mutate(key, endpoint, payload, method = 'PATCH') {
            const analysisId = this.analysisId;
            const mutationRevision = this.requestRevision;
            const previous = mutationQueues.get(analysisId) ?? Promise.resolve();
            this.mutations[key] = (this.mutations[key] ?? 0) + 1;
            this.pendingMutationCount++;

            const request = previous.catch(() => {}).then(async () => {
                this.error = '';
                const currentSocketId = socketId();
                const response = await fetch(endpoint, {
                    method,
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        ...(currentSocketId ? { 'X-Socket-ID': currentSocketId } : {}),
                    },
                    body: JSON.stringify(payload),
                });
                const result = await response.json();
                if (!response.ok) throw new Error(Object.values(result.errors ?? {}).flat().join(' ') || result.message || 'Bevestiging kon niet worden opgeslagen.');
                if (mutationRevision === this.requestRevision && this.analysisId === Number(result.data?.analysis_id)) {
                    this.applyData(result.data);
                }
                return result.data;
            }).catch((error) => {
                if (mutationRevision === this.requestRevision) this.error = error.message;
                return null;
            }).finally(() => {
                if (mutationRevision !== this.requestRevision) return;
                this.mutations[key]--;
                if (this.mutations[key] === 0) delete this.mutations[key];
                this.pendingMutationCount--;

                if (this.pendingMutationCount === 0 && this.pendingRealtimeRefresh) {
                    this.pendingRealtimeRefresh = false;
                    this.load(this.analysisId, this.selectedScope?.df, this.selectedScope?.rep);
                }
            });

            mutationQueues.set(analysisId, request);
            request.finally(() => {
                if (mutationQueues.get(analysisId) === request) mutationQueues.delete(analysisId);
            });

            return request;
        },
        applyUpdatedEvent(event) {
            if (Number(event.sample_id) !== Number(this.sampleId)) return;
            if (Number(event.analysis_id) !== Number(this.analysisId)) return;
            if (event.revision && event.revision === this.lastEventRevision) return;
            this.lastEventRevision = event.revision ?? null;
            if (this.pendingMutationCount > 0) {
                this.pendingRealtimeRefresh = true;
                return;
            }
            this.load(this.analysisId, this.selectedScope?.df, this.selectedScope?.rep);
        },
        queueDecisionPrompt(event) {
            if (!event?.analysis_id || this.decisionPrompt?.analysis_id === event.analysis_id) return;
            this.decisionPrompt = event;
        },
        reset(keepSample = false) {
            this.requestRevision++;
            const sampleId = keepSample ? this.sampleId : null;
            this.analysisId = null;
            this.sampleId = sampleId;
            this.data = null;
            this.selectedScopeKey = null;
            this.dialogOpen = false;
            this.loading = false;
            this.mutations = {};
            this.pendingMutationCount = 0;
            this.pendingRealtimeRefresh = false;
            this.lastEventRevision = null;
            this.error = '';
            this.decisionPrompt = null;
        },
    },
});