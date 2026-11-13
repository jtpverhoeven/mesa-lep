<script setup>
import { computed, ref, watch } from 'vue';
import { ArrowLeft, MessageSquare, Plus, Save } from '@lucide/vue';
import { useConfirmationStore } from '../stores/confirmationStore';
import ConfirmationDateInput from './ConfirmationDateInput.vue';
import ConfirmationRacetrack from './ConfirmationRacetrack.vue';
import { noteHtml } from '../notes.js';
import { vSyncValue } from '../syncValue.js';

defineProps({
    barcode: { type: String, default: '' },
});

const store = useConfirmationStore();
const noteDraft = ref('');
const noteField = ref(null);
const applicableScopes = computed(() => store.data?.scopes?.filter((scope) => scope.applicable) ?? []);
const colonyCount = computed(() => store.selectedEvaluation?.contenders?.length ?? 0);
const finished = computed(() => Boolean(store.selectedScope?.ready));
const confirmedCount = computed(() => finished.value ? (store.selectedEvaluation?.summary?.confirmed ?? 0) : '[?]');
const isPerPlate = computed(() => store.data?.config?.mode === 'per_plate');

watch(
    [() => store.data?.note, () => store.dialogOpen],
    ([note]) => {
        if (document.activeElement !== noteField.value) noteDraft.value = note ?? '';
    },
    { immediate: true },
);

async function saveNote() {
    if (noteDraft.value === (store.data?.note ?? '')) return;

    await store.saveNote(noteDraft.value);
}

function saveSupportValue(field, value) {
    if (field.assurance) store.saveMetadata(field.assurance.key, value);
}

function supportTone(field) {
    if (!(field.assurance.out_of_specification || field.assurance.out_of_date_here)) return '';

    return field.assurance.explanation ? 'explained' : 'warning';
}

function scopeLabel(scope) {
    if (scope.df === 'global') return 'Globaal';

    const value = Number(scope.df);
    const exponent = value > 0 ? -Math.log10(value) : Number.NaN;
    const dilution = Number.isInteger(exponent) ? (exponent === 0 ? '0' : `-${exponent}`) : String(scope.df);
    const replicate = Number(scope.rep) === 0 ? '' : ` · replica ${Number(scope.rep) + 1}`;

    return `${dilution}${replicate}`;
}
</script>

<template>
    <section v-if="store.dialogOpen" class="sample-panel confirmation-view" aria-labelledby="confirmation-title">
        <h2>
            <button class="button" type="button" @click="store.close()"><ArrowLeft :size="15" />Terug naar monster</button>
            <span id="confirmation-title">Bevestiging<template v-if="store.data?.analysis_name"> · {{ store.data.analysis_name }}</template></span>
            <span class="confirmation-context">Monster: {{ barcode }}<template v-if="isPerPlate && store.selectedScope">, Verdunning: {{ scopeLabel(store.selectedScope) }}</template></span>
            <span v-if="store.pendingMutationCount" class="confirmation-saving" role="status">Opslaan...</span>
        </h2>
        <div class="confirmation-body">
            <p v-if="store.error" class="confirmation-error" role="alert">{{ store.error }}</p>
            <div v-if="applicableScopes.length > 1" class="confirmation-scope-tabs" role="tablist" aria-label="Bevestigingsscope">
                <button v-for="scope in applicableScopes" :key="`${scope.df}:${scope.rep}`" type="button" role="tab" :aria-selected="store.selectedScopeKey === `${scope.df}:${scope.rep}`" @click="store.load(store.analysisId, scope.df, scope.rep)">{{ scopeLabel(scope) }}<small>{{ scope.ready ? 'Gereed' : 'Wacht' }}</small></button>
            </div>

            <div class="confirmation-toolbar">
                <h3>Kolonie vormende eenheden</h3>
                <button class="button" type="button" :disabled="store.readOnly || !store.selectedScope" @click="store.addContender"><Plus :size="15" />Kolonie toevoegen</button>
            </div>
            <ConfirmationRacetrack />

            <div v-if="!store.loading" class="confirmation-conclusion">
                <p><strong>Status bevestiging:</strong> <span class="confirmation-state" :class="{ finished }">{{ finished ? 'Afgerond' : 'Nog niet afgerond' }}</span></p>
                <p><strong>Eind conclusie:</strong> In totaal zijn {{ confirmedCount }} van de {{ colonyCount }} kolonie vormende eenheden bevestigde {{ store.data?.analysis_name }}</p>
            </div>

            <div v-if="store.data?.support_fields?.length" class="confirmation-section">
                <h3>Ondersteunende media / materialen</h3>
                <table class="confirmation-support">
                    <thead><tr><th>Media / Materiaal</th><th>THT / Waarde</th></tr></thead>
                    <tbody>
                        <tr v-for="field in store.data.support_fields" :key="field.media_id">
                            <th scope="row"><label><input type="checkbox" :checked="field.active" :disabled="store.readOnly" @change="store.toggleSupport(field.media_id, $event.target.checked)">{{ field.name }}</label></th>
                            <td>
                                <div v-if="field.assurance" class="support-value-cell">
                                    <ConfirmationDateInput
                                        v-if="field.assurance.kind !== 'material'"
                                        :value="field.assurance.value"
                                        :tone="supportTone(field)"
                                        :disabled="store.readOnly || !field.active || !field.assurance.available"
                                        :label="`THT ${field.name}`"
                                        @commit="saveSupportValue(field, $event)"
                                    />
                                    <input
                                        v-else
                                        class="support-value"
                                        :class="supportTone(field)"
                                        v-sync-value="field.assurance.value ?? ''"
                                        :disabled="store.readOnly || !field.active || !field.assurance.available"
                                        :placeholder="field.assurance.acceptable_range || ''"
                                        :aria-label="`Waarde ${field.name}`"
                                        @change="saveSupportValue(field, $event.target.value)"
                                    >
                                    <button v-if="field.assurance.requires_explanation || field.assurance.explanation" class="icon-button" type="button" title="Uitleg" aria-label="Uitleg ondersteunend medium" @click="store.openAssuranceExplanation(field.assurance)"><MessageSquare :size="15" /></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="store.assuranceExplanationField" class="confirmation-section confirmation-explanation-editor">
                <label :for="`confirmation-explanation-${store.assuranceExplanationField.key}`">Uitleg: {{ store.assuranceExplanationField.key }}</label>
                <textarea :id="`confirmation-explanation-${store.assuranceExplanationField.key}`" v-model="store.assuranceExplanation" :disabled="store.readOnly" rows="3"></textarea>
                <div class="confirmation-actions"><button class="button" type="button" @click="store.assuranceExplanationField = null">Sluiten</button><button class="button primary" type="button" :disabled="store.readOnly || store.loading" @click="store.saveAssuranceExplanation"><Save :size="15" />Opslaan</button></div>
            </div>

            <div class="confirmation-section">
                <label for="confirmation-note"><h3>Notitie</h3></label>
                <textarea id="confirmation-note" ref="noteField" v-model="noteDraft" class="confirmation-note" :disabled="store.readOnly" rows="4" @change="saveNote"></textarea>
            </div>

            <div v-for="table in store.data?.tables ?? []" :key="table.id" class="confirmation-section">
                <h3>{{ table.name }}</h3>
                <div class="note-html" v-html="noteHtml(table.html)"></div>
            </div>

            <div class="confirmation-actions"><button class="button primary" type="button" @click="store.close()">Sluiten</button></div>
        </div>
    </section>
</template>

<style scoped>
.confirmation-view h2 { gap:12px; min-height:44px; font-size:15px; }
.confirmation-view h2 .button { min-height:32px; }
.confirmation-context { color:var(--muted); font-size:13px; font-weight:400; }
.confirmation-saving { margin-left:auto; color:var(--muted); font-size:13px; font-weight:400; }
.confirmation-body { display:grid; gap:18px; padding:16px; font-size:13px; }
.confirmation-body h3 { margin:0; font-size:14px; font-weight:700; }
.confirmation-error { margin:0; color:#903e32; font-size:13px; }
.confirmation-scope-tabs { display:flex; overflow:auto; border-bottom:1px solid var(--line); }
.confirmation-scope-tabs button { display:grid; gap:2px; min-width:120px; padding:9px 12px; border:0; border-bottom:2px solid transparent; background:var(--surface); color:var(--muted); font:inherit; text-align:left; cursor:pointer; }
.confirmation-scope-tabs button[aria-selected=true] { border-bottom-color:var(--accent); background:var(--accent-faint); color:var(--accent); }
.confirmation-scope-tabs small { color:inherit; font-size:11px; }
.confirmation-toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; }
.confirmation-conclusion { display:grid; gap:6px; font-size:14px; }
.confirmation-conclusion p { margin:0; }
.confirmation-state { color:#9a5500; font-weight:700; }
.confirmation-state.finished { color:#247448; }
.confirmation-section { display:grid; gap:8px; padding-top:14px; border-top:1px solid var(--line); }
.confirmation-support { width:100%; max-width:560px; border-collapse:collapse; text-align:left; }
.confirmation-support th,.confirmation-support td { padding:6px 8px; border-bottom:1px solid var(--line); vertical-align:middle; }
.confirmation-support thead th { background:var(--surface-alt); color:var(--muted); font-size:12px; }
.confirmation-support tbody th { font-weight:400; }
.confirmation-support label { display:flex; align-items:center; gap:8px; cursor:pointer; }
.support-value-cell { display:flex; align-items:center; gap:6px; }
.support-value { flex:1; min-width:0; min-height:30px; border:1px solid var(--control-border); border-radius:2px; background:var(--surface); color:var(--ink); padding:3px 6px; font:inherit; }
.support-value:disabled { background:var(--surface-alt); color:var(--muted); }
.support-value.warning { border-color:#bf685d; background:#fff1ed; }
.support-value.explained { border-color:#c78a2c; background:#fff2d9; }
.confirmation-note,.confirmation-explanation-editor textarea { display:block; width:100%; resize:vertical; border:1px solid var(--control-border); border-radius:2px; background:var(--surface); color:var(--ink); padding:8px 10px; font:inherit; }
.confirmation-note:disabled { background:var(--surface-alt); color:var(--muted); }
.confirmation-explanation-editor label { font-weight:700; }
.confirmation-actions { display:flex; justify-content:flex-end; gap:8px; }
</style>
