<script setup>
import { computed, ref } from 'vue';
import { LoaderCircle, MessageSquare, Plus, Save, X } from '@lucide/vue';
import Dialog from 'openvue/dialog';
import { useConfirmationStore } from '../stores/confirmationStore';
import AppDialog from './AppDialog.vue';
import ConfirmationRacetrack from './ConfirmationRacetrack.vue';
import { createAppDialogPassThrough } from '../dialogPassThrough';

const store = useConfirmationStore();
const noteDialogOpen = ref(false);
const noteDraft = ref('');
const applicableScopes = computed(() => store.data?.scopes?.filter((scope) => scope.applicable) ?? []);
const dialogPassThrough = createAppDialogPassThrough({ width: '980px', titleId: 'confirmation-title' });

function openNoteDialog() {
    noteDraft.value = store.data?.note ?? '';
    noteDialogOpen.value = true;
}

async function saveNote() {
    const result = await store.saveNote(noteDraft.value);

    if (result) {
        noteDialogOpen.value = false;
    }
}

function saveSupportValue(field, event) {
    if (!field.assurance) return;
    store.saveMetadata(field.assurance.key, event.target.value);
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
    <Dialog v-model:visible="store.dialogOpen" modal :draggable="false" :dismissable-mask="true" :close-on-escape="false" :closable="false" :block-scroll="true" :unstyled="true" :pt="dialogPassThrough">
        <template #header>
            <div><h2 id="confirmation-title" class="m-0 text-[16px]">Bevestiging</h2><small v-if="store.data" class="mt-[3px] block text-[var(--muted)]">{{ store.data.status }}</small></div>
            <button class="icon-button" type="button" title="Sluiten" aria-label="Bevestiging sluiten" @click="store.close()"><X :size="16" /></button>
        </template>
        <div class="grid gap-[14px] p-[14px]">
            <p v-if="store.error" class="error-text" role="alert">{{ store.error }}</p>
            <div v-if="applicableScopes.length > 1" class="confirmation-scope-tabs" role="tablist" aria-label="Bevestigingsscope">
                <button v-for="scope in applicableScopes" :key="`${scope.df}:${scope.rep}`" type="button" :aria-selected="store.selectedScopeKey === `${scope.df}:${scope.rep}`" @click="store.load(store.analysisId, scope.df, scope.rep)">{{ scopeLabel(scope) }}<small>{{ scope.ready ? 'Gereed' : 'Wacht' }}</small></button>
            </div>
            <div class="confirmation-summary"><strong>{{ store.selectedEvaluation?.summary?.confirmed ?? 0 }}/{{ store.selectedEvaluation?.summary?.tested ?? 0 }}</strong><span>ratio {{ store.selectedEvaluation?.summary?.ratio ?? '-' }}</span><span>{{ store.selectedScope?.ready ? 'Gereed' : 'Nog niet gereed' }}</span><span v-if="store.pendingMutationCount" role="status">Opslaan...</span></div>
            <ConfirmationRacetrack />
            <div class="confirmation-support-list">
                <label v-for="field in store.data?.support_fields ?? []" :key="field.media_id">
                    <span class="support-toggle"><input type="checkbox" :checked="field.active" :disabled="store.readOnly" @change="store.toggleSupport(field.media_id, $event.target.checked)">{{ field.name }}</span>
                    <input v-if="field.assurance" class="support-value" :class="{ 'support-warning': field.assurance.out_of_specification || field.assurance.out_of_date_here }" :value="field.assurance.value ?? ''" :disabled="store.readOnly || !field.active || !field.assurance.available" :placeholder="field.assurance.kind === 'material' ? (field.assurance.acceptable_range || '') : 'dd-mm-jjjj'" @change="saveSupportValue(field, $event)">
                    <button v-if="field.assurance?.requires_explanation || field.assurance?.explanation" class="icon-button" type="button" title="Uitleg" aria-label="Uitleg ondersteunend medium" @click="store.openAssuranceExplanation(field.assurance)"><MessageSquare :size="14" /></button>
                </label>
            </div>
            <div class="confirmation-note-summary">
                <div class="confirmation-note-heading"><span>Notitie</span><button class="button" type="button" @click="openNoteDialog"><MessageSquare :size="14" />{{ store.data?.note ? 'Notitie bewerken' : 'Notitie toevoegen' }}</button></div>
                <p v-if="store.data?.note" class="confirmation-note-preview">{{ store.data.note }}</p>
            </div>
            <div v-if="store.assuranceExplanationField" class="confirmation-explanation-editor">
                <label :for="`confirmation-explanation-${store.assuranceExplanationField.key}`">Uitleg: {{ store.assuranceExplanationField.key }}</label>
                <textarea :id="`confirmation-explanation-${store.assuranceExplanationField.key}`" v-model="store.assuranceExplanation" :disabled="store.readOnly" rows="3"></textarea>
                <div class="confirmation-explanation-actions"><button class="button" type="button" @click="store.assuranceExplanationField = null">Sluiten</button><button class="button primary" type="button" :disabled="store.readOnly || store.loading" @click="store.saveAssuranceExplanation"><Save :size="15" />Opslaan</button></div>
            </div>
        </div>
        <template #footer>
                <button class="button" type="button" :disabled="store.readOnly || !store.selectedScope" @click="store.addContender"><Plus :size="15" />Kolonie toevoegen</button>
                <button v-if="store.selectedEvaluation?.contenders?.length" class="button" type="button" :disabled="store.readOnly" @click="store.removeContender(store.selectedEvaluation.contenders.at(-1).index)">Laatste verwijderen</button>
                <button class="button primary" type="button" :disabled="store.loading" @click="store.close"><Save :size="15" />Sluiten</button>
        </template>
    </Dialog>
    <AppDialog v-model:visible="noteDialogOpen" title="Notitie">
        <textarea v-model="noteDraft" class="confirmation-note-editor" :disabled="store.readOnly" rows="7" autofocus aria-label="Notitie"></textarea>
        <template #footer>
            <button class="button" type="button" @click="noteDialogOpen = false">Annuleren</button>
            <button class="button primary" type="button" :disabled="store.readOnly || store.pendingMutationCount > 0" @click="saveNote"><Save :size="15" />Opslaan</button>
        </template>
    </AppDialog>
</template>

<style scoped>
.confirmation-scope-tabs { display:flex; overflow:auto; border-bottom:1px solid var(--line); }
.confirmation-scope-tabs button { display:grid; gap:2px; min-width:110px; padding:8px 10px; border:0; border-bottom:2px solid transparent; background:var(--surface); color:var(--muted); font:inherit; text-align:left; cursor:pointer; }
.confirmation-scope-tabs button[aria-selected=true] { border-bottom-color:var(--accent); background:var(--accent-faint); color:var(--accent); }
.confirmation-scope-tabs small { color:inherit; font-size:10px; }
.confirmation-summary { display:flex; flex-wrap:wrap; gap:14px; color:var(--muted); font-size:12px; }
.confirmation-summary strong { color:var(--ink); }
.confirmation-support-list { display:flex; flex-wrap:wrap; gap:10px 16px; padding-top:10px; border-top:1px solid var(--line); }
.confirmation-support-list label { display:flex; align-items:center; justify-content:space-between; gap:9px; font-size:11px; }
.support-toggle { display:flex; align-items:center; gap:6px; min-width:0; }
.support-value { width:96px; min-height:27px; border:1px solid #9eabb2; background:var(--surface); color:var(--ink); padding:4px 6px; font:inherit; font-size:11px; }
.support-value:disabled { background:var(--surface-alt); color:var(--muted); }
.support-value.support-warning { border-color:#bf685d; background:#fff1ed; }
.confirmation-note-summary { display:grid; gap:7px; padding-top:10px; border-top:1px solid var(--line); }
.confirmation-note-heading { display:flex; align-items:center; justify-content:space-between; gap:10px; font-size:11px; font-weight:600; }
.confirmation-note-heading .button { min-height:30px; padding:5px 9px; font-size:11px; }
.confirmation-note-preview { margin:0; padding:7px 9px; border:1px solid var(--line); background:var(--surface-alt); color:var(--ink); font-size:11px; white-space:pre-wrap; }
.confirmation-note-editor { display:block; width:100%; min-height:170px; resize:vertical; border:1px solid #9eabb2; background:var(--surface); color:var(--ink); padding:8px 9px; font:inherit; }
.confirmation-explanation-editor { display:grid; gap:6px; padding-top:10px; border-top:1px solid var(--line); font-size:11px; font-weight:600; }
.confirmation-explanation-editor textarea { width:100%; resize:vertical; border:1px solid #9eabb2; background:var(--surface); color:var(--ink); padding:7px 9px; font:inherit; }
.confirmation-explanation-actions { display:flex; justify-content:flex-end; gap:8px; }
</style>