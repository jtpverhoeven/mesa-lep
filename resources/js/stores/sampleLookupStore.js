import { defineStore } from 'pinia';
import { useSampleResearchStore } from './sampleResearchStore.js';

export const useSampleLookupStore = defineStore('sampleLookup', {
    state: () => ({ endpoints: {}, data: null, barcode: '', loading: false, saving: false, error: '', selectedId: null, scannedPlateFollowNumber: null, plateFocusRevision: 0, tab: 'general', adding: false, requestId: 0, debug: null, resultData: null, resultsLoading: false, resultSaving: {}, resultError: '', calculation: null, calculationLoading: false, calculationError: '', calculationRevision: 0, resultRequestId: 0 }),
    getters: {
        selected: (state) => state.data?.analyses.find((analysis) => analysis.id === state.selectedId) ?? null,
        progress: (state) => state.data?.analyses.length ? Math.round(state.data.analyses.filter((analysis) => analysis.is_ready).length / state.data.analyses.length * 100) : 0,
        readOnly: (state) => Boolean(Number(state.data?.project?.locked) || Number(state.data?.project?.auth_status)),
    },
    actions: {
        async lookup(barcode = this.barcode) {
            if (this.saving) return;
            const requestId = ++this.requestId;
            const scannedBarcode = String(barcode ?? '').trim();
            const [sampleBarcode, analysisFollowNumber, plateFollowNumber] = scannedBarcode.split('.');
            this.barcode = scannedBarcode;
            this.scannedPlateFollowNumber = plateFollowNumber || null;
            const replaceBarcodeUrl = () => {
                const url = new URL(window.location.href);
                url.searchParams.set('barcode', this.barcode);
                window.history.replaceState({}, '', url);
            };
            if (sampleBarcode && String(this.data?.sample.barcode) === sampleBarcode) {
                const selectedId = this.data.analyses.find((analysis) => String(analysis.follow_number) === analysisFollowNumber)?.id ?? this.data.analyses[0]?.id ?? null;
                this.error = '';
                this.debug = null;
                this.adding = false;
                replaceBarcodeUrl();
                if (this.selectedId === selectedId) this.plateFocusRevision++;
                else this.selectedId = selectedId;
                return;
            }
            this.data = null;
            this.selectedId = null;
            this.clearResults();
            this.debug = null;
            this.adding = false;
            this.error = '';
            this.tab = 'general';
            useSampleResearchStore().reset();
            this.loading = false;
            if (!sampleBarcode) return;
            this.loading = true;
            try {
                const response = await fetch(`${this.endpoints.lookup}?${new URLSearchParams({ barcode: sampleBarcode })}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error(response.status === 404 ? 'Geen monster gevonden met deze barcode.' : 'Monster kon niet worden geladen.');
                const { data } = await response.json();
                if (requestId !== this.requestId) return;
                this.data = data;
                this.selectedId = data.analyses.find((analysis) => String(analysis.follow_number) === analysisFollowNumber)?.id ?? data.analyses[0]?.id ?? null;
                replaceBarcodeUrl();
            } catch (error) {
                if (requestId === this.requestId) this.error = error.message;
            } finally {
                if (requestId === this.requestId) this.loading = false;
            }
        },
        async openResearch() {
            if (!this.data || this.saving || this.readOnly) return;
            this.adding = true;
            const research = useSampleResearchStore();
            research.configure(this.endpoints.options.replace('__SAMPLE__', this.data.sample.id));
            await research.load(this.data.sample.client);
        },
        clearResults() {
            this.resultRequestId++;
            this.calculationRevision++;
            this.resultData = null;
            this.resultsLoading = false;
            this.resultSaving = {};
            this.resultError = '';
            this.calculation = null;
            this.calculationLoading = false;
            this.calculationError = '';
        },
        async loadResults(analysisId = this.selectedId) {
            const requestId = ++this.resultRequestId;
            this.calculationRevision++;
            const calculationRevision = this.calculationRevision;
            this.resultData = null;
            this.resultError = '';
            this.calculation = null;
            this.calculationError = '';
            if (!analysisId) {
                this.resultsLoading = false;
                this.calculationLoading = false;
                return;
            }

            this.resultsLoading = true;
            this.calculationLoading = true;
            try {
                const endpoint = this.endpoints.results.replace('__ANALYSIS__', analysisId);
                const response = await fetch(endpoint, { headers: { Accept: 'application/json' } });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Resultaatvelden konden niet worden geladen.');
                result.data.rows = result.data.rows.map((row) => ({ ...row, data: { ...(row.data ?? {}) } }));
                if (requestId === this.resultRequestId && this.selectedId === analysisId) {
                    this.resultData = result.data;
                    if (this.calculationRevision === calculationRevision) {
                        this.applyCalculation(analysisId, result.data.calculation);
                        this.calculationLoading = Boolean(result.data.calculation_queued) && !result.data.calculation;
                        this.calculationError = result.data.calculation_unavailable || '';
                    }
                }
            } catch (error) {
                if (requestId === this.resultRequestId) {
                    this.resultError = error.message;
                    this.calculationError = error.message;
                    this.calculationLoading = false;
                }
            } finally {
                if (requestId === this.resultRequestId) this.resultsLoading = false;
            }
        },
        async saveResult(row, field) {
            if (!this.selectedId || this.readOnly) return;
            const analysisId = this.selectedId;
            const key = `${row.id}:${field}`;
            this.resultSaving[key] = true;
            this.resultError = '';
            this.markCalculationQueued(analysisId);

            try {
                const endpoint = this.endpoints.updateResult
                    .replace('__ANALYSIS__', analysisId)
                    .replace('__RESULT__', row.id);
                const response = await fetch(endpoint, {
                    method: 'PATCH',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
                    body: JSON.stringify({ field, value: String(row.data[field] ?? '') }),
                });
                const result = await response.json();
                if (!response.ok) throw new Error(Object.values(result.errors ?? {}).flat().join(' ') || result.message || 'Resultaat kon niet worden opgeslagen.');
                if (this.selectedId === analysisId) {
                    Object.assign(row, result.data);
                    if (this.resultData && result.confirmation) this.resultData.confirmation = result.confirmation;
                }
            } catch (error) {
                this.resultError = error.message;
                this.calculationError = error.message;
                this.calculationLoading = false;
            } finally {
                delete this.resultSaving[key];
            }
        },
        applyCalculation(analysisId, calculation) {
            const analysis = this.data?.analyses.find((item) => item.id === analysisId);
            if (analysis && calculation) analysis.is_ready = Boolean(calculation.isReady);
            if (this.selectedId !== analysisId) return;
            this.calculationRevision++;
            this.calculation = calculation ?? null;
            this.calculationLoading = false;
            this.calculationError = '';
        },
        markCalculationQueued(analysisId) {
            const analysis = this.data?.analyses.find((item) => item.id === analysisId);
            if (analysis) analysis.is_ready = false;
            if (this.selectedId !== analysisId) return;
            this.calculationRevision++;
            this.calculation = null;
            this.calculationLoading = true;
            this.calculationError = '';
        },
        applyCalculationEvent(event) {
            if (Number(event.sample_id) !== Number(this.data?.sample.id)) return;
            this.applyCalculation(Number(event.analysis_id), event.calculation);
        },
        applyCalculationFailure(event) {
            if (Number(event.sample_id) !== Number(this.data?.sample.id)) return;
            const analysis = this.data?.analyses.find((item) => item.id === Number(event.analysis_id));
            if (analysis) analysis.is_ready = false;
            if (Number(event.analysis_id) !== Number(this.selectedId)) return;
            this.calculationRevision++;
            this.calculationLoading = false;
            this.calculationError = event.message || 'Eindresultaat kon niet worden berekend.';
        },
        reportRealtimeError() {
            if (!this.calculationLoading) return;
            this.calculationLoading = false;
            this.calculationError = 'Live updates zijn niet beschikbaar. Probeer het eindresultaat opnieuw.';
        },
        placeholder(feature) {
            this.debug = { feature, implemented: false, sample_id: this.data?.sample.id ?? null, barcode: this.data?.sample.barcode ?? null, project_id: this.data?.project?.id ?? null, sampleanalysis_id: this.selectedId, assay_base: this.selected?.assay_base ?? null };
        },
        async mutate(payload) {
            if (!this.data || this.saving || this.loading || this.readOnly) return;
            this.saving = true;
            this.error = '';
            try {
                const response = await fetch(this.endpoints.research.replace('__SAMPLE__', this.data.sample.id), {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
                    body: JSON.stringify(payload),
                });
                const result = await response.json();
                if (!response.ok) throw new Error(Object.values(result.errors ?? {}).flat().join(' ') || result.message || 'Wijziging mislukt.');
                this.data = result.data;
                if (!this.selected) this.selectedId = this.data.analyses[0]?.id ?? null;
                this.adding = false;
                useSampleResearchStore().reset();
            } catch (error) {
                this.error = error.message;
            } finally {
                this.saving = false;
            }
        },
        move(offset) {
            const ids = this.data.analyses.map((analysis) => analysis.id);
            const index = ids.indexOf(this.selectedId);
            const target = index + offset;
            if (index < 0 || target < 0 || target >= ids.length) return;
            [ids[index], ids[target]] = [ids[target], ids[index]];
            return this.mutate({ operation: 'reorder', analysis_ids: ids });
        },
    },
});