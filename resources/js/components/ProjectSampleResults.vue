<script setup>
import { computed, ref, watch } from 'vue';
import { Check, Eye, EyeOff, Pencil, ShieldCheck } from '@lucide/vue';
import DOMPurify from 'dompurify';
import Button from 'openvue/button';
import InputText from 'openvue/inputtext';
import Select from 'openvue/select';
import Textarea from 'openvue/textarea';
import { useProjectSearchStore } from '../stores/projectSearchStore';
import AppDialog from './AppDialog.vue';

const props = defineProps({ canVeto: { type: Boolean, default: false } });
const store = useProjectSearchStore();
const canEdit = computed(() => props.canVeto && !store.sampleReadOnly);
const editor = ref(null);
const vetoDetails = ref(null);
const value = ref('');
const reason = ref('');
const disposition = ref(null);
const removing = ref(false);
const dispositions = [
    { value: '+', label: 'Fout: Rood op rapport & portal - Deze waarde is Fout' },
    { value: '-', label: 'Goed: Zwart op rapport & groen in portal - Deze waarde is een verwacht resultaat' },
    { value: '.', label: 'Neutraal:  Zwart op rapport & grijs in portal - Aan deze waarde kan geen beoordeling worden gegeven' },
];
const canSubmit = computed(() => canEdit.value && reason.value.trim()
    && (removing.value || (value.value.trim() && (!editor.value?.requiresDisposition || disposition.value !== null))));

watch(() => [store.selectedProjectId, store.selectedSampleId, canEdit.value], () => {
    editor.value = null;
    vetoDetails.value = null;
});

function editVeto(analysis, result) {
    store.vetoError = '';
    removing.value = false;
    value.value = result.veto?.result ?? '';
    reason.value = result.veto?.reason ?? '';
    disposition.value = result.veto?.disposition ?? null;
    editor.value = {
        analysisId: analysis.id,
        name: analysis.name,
        parameter: result.key,
        unit: result.unit,
        veto: result.veto,
        requiresDisposition: analysis.requiresDisposition,
    };
}

function startRemoval() {
    removing.value = true;
    reason.value = '';
    store.vetoError = '';
}

async function submitVeto() {
    if (!editor.value || !canSubmit.value || store.savingVeto) return;

    const success = await store.changeVeto(editor.value.analysisId, {
        parameter: editor.value.parameter,
        value: value.value,
        reason: reason.value,
        disposition: disposition.value,
    }, removing.value);
    if (success) editor.value = null;
}

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
    results = results.map((result) => ({
        ...result,
        veto: response?.data?.veto_results?.find((veto) => veto.parameter === result.key),
    }));

    if (!results.length) {
        results = [{
            key: 'empty',
            unit: response?.data?.unit || '-',
            value: display(response?.error || response?.data?.calculation_unavailable
                || (response?.data?.calculation_queued ? 'Eindresultaat berekenen...' : 'Nog geen uitslag beschikbaar.')),
        }];
    }

    return {
        ...analysis,
        results,
        reference: response?.data?.reference,
        ready: Boolean(calculation?.isReady ?? analysis.is_ready),
        requiresDisposition: Boolean(response?.data?.veto_requires_disposition),
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
    <div class="border-t border-line pt-2">
        <p v-if="!analyses.length" class="empty-state">Geen onderzoek voor dit monster.</p>
        <div v-else class="overflow-x-auto">
            <table class="result-table">
                <caption class="sr-only">Analyseresultaten voor het geselecteerde monster</caption>
                <thead>
                    <tr>
                        <th scope="col">Eenheid</th>
                        <th scope="col" class="w-full">Resultaat</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody v-for="analysis in analyses" :key="analysis.id">
                    <tr>
                        <th scope="rowgroup" colspan="3" class="bg-surface-alt font-medium wrap-anywhere">{{ analysis.name }}</th>
                    </tr>
                    <tr v-for="(result, index) in analysis.results" :key="`${analysis.id}:${result.key}`">
                        <td class="whitespace-nowrap">{{ result.unit }}</td>
                        <td class="wrap-anywhere">
                            <div class="flex items-center gap-1">
                                <span class="min-w-0">
                                    <span class="font-medium" v-html="result.value"></span>
                                    <template v-for="addendum in result.addenda" :key="addendum.label">
                                        <sup v-if="addendum.marker" class="ml-0.5 font-semibold text-danger" :title="addendum.label">{{ addendum.marker }}</sup>
                                        <span v-else class="ml-1 text-muted">{{ addendum.label }}</span>
                                    </template>
                                </span>
                                <Button v-if="canEdit && result.key !== 'empty'" class="result-table-action" severity="secondary" variant="text"
                                    :aria-label="`${result.veto ? 'Veto bewerken' : 'Veto instellen'}: ${analysis.name}, ${result.unit}`"
                                    :title="result.veto ? 'Veto bewerken' : 'Veto instellen'"
                                    :disabled="store.savingVeto" @click="editVeto(analysis, result)">
                                    <Pencil :size="14" />
                                </Button>
                            </div>
                            <p v-if="index === 0" class="mt-1 text-muted">Ref: {{ reference(analysis.reference) }}</p>
                        </td>
                        <td>
                            <div class="flex items-center gap-1 text-muted">
                                <Check v-if="analysis.ready" class="shrink-0 text-success" :size="14" role="img" aria-label="Gereed" />
                                <component :is="confirmationStatus(analysis).icon" v-if="confirmationStatus(analysis)" class="shrink-0" :size="14" role="img" :aria-label="confirmationStatus(analysis).label" />
                                <Button v-if="result.veto" class="result-table-action" variant="text"
                                    :aria-label="`Veto reden bekijken: ${analysis.name}, ${result.unit}`" title="Veto reden bekijken"
                                    @click="vetoDetails = { name: analysis.name, unit: result.unit, reason: result.veto.reason }">
                                    <ShieldCheck :size="14" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <AppDialog :visible="vetoDetails !== null" title="Reden vetoresultaat" @update:visible="!$event && (vetoDetails = null)">
            <div v-if="vetoDetails" class="grid gap-3 p-4">
                <p class="font-semibold">{{ vetoDetails.name }} - {{ vetoDetails.unit }}</p>
                <p class="whitespace-pre-wrap wrap-anywhere">{{ vetoDetails.reason || 'Geen reden vastgelegd in de legacygegevens.' }}</p>
            </div>
            <template #footer>
                <Button severity="secondary" label="Sluiten" @click="vetoDetails = null" />
            </template>
        </AppDialog>
        <AppDialog :visible="editor !== null" :title="removing ? 'Vetoresultaat verwijderen' : 'Vetoresultaat instellen'"
            :closable="!store.savingVeto" :dismissable-mask="!store.savingVeto" :close-on-escape="!store.savingVeto"
            @update:visible="!$event && (editor = null)">
            <form v-if="editor" id="project-veto-form" class="grid gap-4 p-4" @submit.prevent="submitVeto">
                <p class="font-semibold">{{ editor.name }} - {{ editor.unit }}</p>
                <p v-if="removing" class="text-muted">Het automatisch berekende resultaat wordt opnieuw gebruikt. Geef een nieuwe reden voor het verwijderen op.</p>
                <div v-if="!removing" class="field">
                    <label for="veto-value">Vetoresultaat</label>
                    <InputText id="veto-value" v-model="value" required maxlength="512" :disabled="store.savingVeto" autofocus />
                </div>
                <div v-if="!removing && editor.requiresDisposition" class="field">
                    <label id="veto-disposition-label" for="veto-disposition">Type uitslag</label>
                    <Select v-model="disposition" input-id="veto-disposition" aria-labelledby="veto-disposition-label" :options="dispositions" option-label="label"
                        option-value="value" placeholder="Selecteer een van deze opties" :disabled="store.savingVeto" />
                </div>
                <div class="field">
                    <label for="veto-reason">{{ removing ? 'Reden verwijderen' : 'Reden wijziging' }} (verplicht)</label>
                    <Textarea id="veto-reason" v-model="reason" required maxlength="512" rows="3" :disabled="store.savingVeto" />
                </div>
                <p v-if="store.vetoError" class="text-danger" role="alert">{{ store.vetoError }}</p>
            </form>
            <template #footer>
                <div class="flex w-full flex-wrap justify-end gap-2">
                    <Button v-if="editor?.veto && !removing" severity="danger" variant="outlined" label="Veto verwijderen"
                        :disabled="store.savingVeto" @click="startRemoval" />
                    <Button severity="secondary" label="Annuleren" :disabled="store.savingVeto" @click="editor = null" />
                    <Button type="submit" form="project-veto-form" :severity="removing ? 'danger' : 'primary'"
                        :label="removing ? 'Verwijderen bevestigen' : 'Opslaan'" :loading="store.savingVeto" :disabled="!canSubmit || store.savingVeto" />
                </div>
            </template>
        </AppDialog>
    </div>
</template>