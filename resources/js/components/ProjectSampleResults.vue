<script setup>
import { computed } from 'vue';
import { Check, Eye, EyeOff } from '@lucide/vue';
import DOMPurify from 'dompurify';
import { useProjectSearchStore } from '../stores/projectSearchStore';

const store = useProjectSearchStore();

function display(value) {
    const normalized = value !== null && typeof value === 'object' ? JSON.stringify(value) : String(value ?? '-');
    return DOMPurify.sanitize(normalized, { ALLOWED_TAGS: ['sup'], ALLOWED_ATTR: [] });
}

function resultAddenda(calculation, key, displayedValue) {
    if (key !== calculation?.reportIn || !calculation?.addenda) return [];

    const values = Array.isArray(calculation.addenda) ? calculation.addenda : [calculation.addenda];

    return values.map((item) => {
        const code = typeof item === 'object' ? String(item.code ?? '') : '';
        const label = typeof item === 'object' ? String(item.label ?? item.text ?? code) : String(item);
        const normalized = code.toLowerCase().replaceAll('-', '_').replaceAll(' ', '_');
        const marker = normalized === 'indicative' ? '*' : normalized === 'not_confirmed' ? '**' : null;

        return { label, marker };
    }).filter((item) => item.label && (!item.marker || !displayedValue.includes(`<sup>${item.marker}</sup>`)));
}

function visibleResults(calculation, reportUnit) {
    const hidden = new Set(Array.isArray(calculation?.resultHide) ? calculation.resultHide : []);

    return Object.entries(calculation?.output ?? {})
        .filter(([key]) => !hidden.has(key))
        .map(([key, value]) => {
            const displayedValue = display(value ?? calculation?.outputEn?.[key]);

            return {
                key,
                unit: key === calculation?.reportIn && reportUnit ? reportUnit : (calculation?.resultMask?.[key] || key),
                value: displayedValue,
                addenda: resultAddenda(calculation, key, displayedValue),
            };
        });
}

const analyses = computed(() => (store.sampleData?.analyses ?? []).map((analysis) => {
    const response = store.analysisResults[analysis.id];
    const calculation = response?.data?.calculation;
    let results = visibleResults(calculation, response?.data?.unit);

    if (!results.length) {
        results = [{
            key: 'empty',
            unit: response?.data?.unit || '-',
            value: display(response?.error || response?.data?.calculation_unavailable || 'Nog geen uitslag beschikbaar.'),
        }];
    }

    return {
        ...analysis,
        results,
        reference: response?.data?.reference,
        ready: Boolean(calculation?.isReady ?? analysis.is_ready),
    };
}));

function confirmationStatus(analysis) {
    if (Number(analysis.conf_requested) === 1) return { icon: Eye, label: 'Bevestiging actief' };
    if (Number(analysis.conf_requested) === 2) return { icon: EyeOff, label: 'Bevestiging uitgeschakeld' };
    return null;
}

function reference(value) {
    if (value === null || typeof value === 'undefined' || value === '') return '-';
    return typeof value === 'object' ? JSON.stringify(value) : value;
}
</script>

<template>
    <div class="project-sample-results">
        <p v-if="!analyses.length" class="empty-state">Geen onderzoek voor dit monster.</p>
        <div v-else class="project-result-scroll">
            <table class="project-result-table">
                <thead>
                    <tr><th>Analyse</th><th>Eenheid</th><th>Resultaat</th><th>Status</th></tr>
                </thead>
                <tbody v-for="analysis in analyses" :key="analysis.id">
                    <tr v-for="(result, index) in analysis.results" :key="`${analysis.id}:${result.key}`">
                        <td v-if="index === 0" :rowspan="analysis.results.length" class="project-result-assay">{{ analysis.name }}</td>
                        <td class="project-result-unit">{{ result.unit }}</td>
                        <td class="project-result-value">
                            <span v-html="result.value"></span>
                            <template v-for="addendum in result.addenda" :key="addendum.label">
                                <sup v-if="addendum.marker" class="project-result-addendum" :title="addendum.label">{{ addendum.marker }}</sup>
                                <small v-else class="project-result-addendum-text">{{ addendum.label }}</small>
                            </template>
                            <small v-if="index === 0"><em>Ref: {{ reference(analysis.reference) }}</em></small>
                        </td>
                        <td class="project-result-status">
                            <Check v-if="analysis.ready" class="project-result-ready" :size="13" aria-label="Gereed" />
                            <component :is="confirmationStatus(analysis).icon" v-if="confirmationStatus(analysis)" :size="13" :aria-label="confirmationStatus(analysis).label" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<style scoped>
.project-sample-results { padding-top:5px; border-top:1px solid var(--line); }
.project-result-scroll { overflow-x:auto; }
.project-result-table { width:100%; border-collapse:collapse; table-layout:fixed; font-size:11px; line-height:1.35; }
.project-result-table th,.project-result-table td { padding:4px 5px; border:1px solid var(--line); text-align:left; vertical-align:top; overflow-wrap:anywhere; }
.project-result-table th { background:var(--surface-alt); font-weight:700; }
.project-result-table th:first-child { width:40%; }
.project-result-table th:nth-child(2) { width:15%; }
.project-result-table th:nth-child(4) { width:54px; }
.project-result-table tbody + tbody tr:first-child > * { border-top-color:#9eabb2; }
.project-result-assay { font-weight:500; }
.project-result-unit { white-space:normal; }
.project-result-value small { display:block; margin-top:2px; color:var(--muted); }
.project-result-addendum,.project-result-addendum-text { margin-left:2px; color:#903e32; font-weight:700; }
.project-result-addendum-text { display:inline !important; }
.project-result-status { color:var(--muted); text-align:right !important; white-space:nowrap; }
.project-result-status svg { display:inline-block; margin-left:3px; vertical-align:middle; }
.project-result-ready { color:#247448; }
</style>