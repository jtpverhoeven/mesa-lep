<script setup>
import { nextTick } from 'vue';
import { LoaderCircle, MessageSquare, X } from '@lucide/vue';
import { useConfirmationStore } from '../stores/confirmationStore';
import ConfirmationDateInput from './ConfirmationDateInput.vue';
import { vSyncValue } from '../syncValue.js';

const store = useConfirmationStore();

function answerValue(contender, step) {
    return contender.answers?.[String(step.index)];
}

function stepIsVisible(contender, step) {
    if (Array.isArray(contender.visible_steps)) return contender.visible_steps.includes(step.index);

    return Object.prototype.hasOwnProperty.call(contender.answers ?? {}, String(step.index));
}

function stepHasVisibleContender(step) {
    return (store.selectedEvaluation?.contenders ?? []).some((contender) => stepIsVisible(contender, step));
}

function answerState(contender, step) {
    const answer = answerValue(contender, step);
    if (!answer) return '';
    if (step.disposition === '?') return 'answer-ambiguous';

    return answer === step.disposition ? 'answer-matching' : 'answer-mismatching';
}

function metadataField(step, suffix) {
    return (store.selectedEvaluation?.metadataFields ?? []).find((field) => field.key === `${step.media_id}_${suffix}`);
}

function saveAnswer(contender, step, value) {
    store.saveTrackAnswer(contender.index, step.index, value);
}

function typeAnswer(contender, step, event) {
    if (!['+', '-'].includes(event.key)) return;

    event.preventDefault();
    event.target.value = event.key;
    saveAnswer(contender, step, event.key);
}

function normalizeAnswer(contender, step, event) {
    const value = event.target.value.replace(/[^+-]/g, '').slice(-1);
    event.target.value = value;
    saveAnswer(contender, step, value);
}

async function moveFocus(contenderIndex, stepIndex, direction) {
    await nextTick();
    document.querySelector(`[data-confirmation-cell="${contenderIndex}:${stepIndex + direction}"]`)?.focus();
}

function navigate(contender, step, event) {
    if (event.key === 'Enter' || event.key === 'ArrowDown') {
        event.preventDefault();
        moveFocus(contender.index, step.index, 1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        moveFocus(contender.index, step.index, -1);
    }
}

function metadata(field, event) {
    if (!field) return;
    store.saveMetadata(field.key, event.target.value);
}

function saveDate(field, value) {
    if (field) store.saveMetadata(field.key, value);
}

function thtTone(field) {
    const flagged = field.out_of_specification || field.out_of_date_here;
    if (!flagged) return '';

    return field.explanation ? 'explained' : 'warning';
}

function removeContender(contender) {
    const hasAnswers = Object.values(contender.answers ?? {}).some((answer) => answer !== null && answer !== '');
    if (hasAnswers && !window.confirm(`Kolonie ${contender.index + 1} verwijderen?`)) return;

    store.removeContender(contender.index);
}
</script>

<template>
    <div class="confirmation-racetrack">
        <div v-if="store.loading" class="sample-loading" role="status"><LoaderCircle class="spin" :size="18" />Bevestiging laden...</div>
        <template v-else>
            <div class="confirmation-grid-scroll">
                <table class="confirmation-grid" :style="{ minWidth: `${770 + (store.selectedEvaluation?.contenders?.length ?? 0) * 88}px` }">
                    <thead>
                        <tr>
                            <th class="medium-column">Medium</th>
                            <th class="date-column">Inzetdatum</th>
                            <th v-for="contender in store.selectedEvaluation?.contenders ?? []" :key="contender.index" class="colony-heading">
                                <span>Kolonie {{ contender.index + 1 }}</span>
                                <button class="colony-remove" type="button" :disabled="store.readOnly" :title="`Kolonie ${contender.index + 1} verwijderen`" :aria-label="`Kolonie ${contender.index + 1} verwijderen`" @click="removeContender(contender)"><X :size="14" /></button>
                            </th>
                            <th class="date-column">Afleesdatum</th>
                            <th class="control-column">Pos.</th>
                            <th class="control-column">Neg.</th>
                            <th class="control-column">Blanco</th>
                            <th class="tht-column">THT datum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="step in store.data?.config.steps ?? []" :key="step.index">
                            <tr v-if="stepHasVisibleContender(step)">
                                <th>{{ step.name }} <strong>{{ step.disposition }}</strong></th>
                                <td class="date-column"><ConfirmationDateInput v-if="metadataField(step, 'inzet')" :value="metadataField(step, 'inzet').value" :disabled="store.readOnly" :label="`Inzetdatum ${step.name}`" @commit="saveDate(metadataField(step, 'inzet'), $event)" /></td>
                                <td v-for="contender in store.selectedEvaluation?.contenders ?? []" :key="contender.index" class="answer-cell">
                                    <input
                                        v-if="stepIsVisible(contender, step)"
                                        v-sync-value="answerValue(contender, step) ?? ''"
                                        :class="answerState(contender, step)"
                                        :disabled="store.readOnly || answerValue(contender, step) === null"
                                        :data-confirmation-cell="`${contender.index}:${step.index}`"
                                        :aria-label="`Kolonie ${contender.index + 1}, ${step.name}`"
                                        maxlength="1"
                                        autocomplete="off"
                                        spellcheck="false"
                                        @keydown="typeAnswer(contender, step, $event); navigate(contender, step, $event)"
                                        @input="normalizeAnswer(contender, step, $event)"
                                        @focus="$event.target.select()"
                                    >
                                </td>
                                <td class="date-column"><ConfirmationDateInput v-if="metadataField(step, 'aflees')" :value="metadataField(step, 'aflees').value" :disabled="store.readOnly" :label="`Afleesdatum ${step.name}`" @commit="saveDate(metadataField(step, 'aflees'), $event)" /></td>
                                <td class="control-column"><input v-if="metadataField(step, 'poscontrol')" v-sync-value="metadataField(step, 'poscontrol').value ?? ''" :disabled="store.readOnly" maxlength="1" aria-label="Positieve controle" @change="metadata(metadataField(step, 'poscontrol'), $event)"></td>
                                <td class="control-column"><input v-if="metadataField(step, 'negcontrol')" v-sync-value="metadataField(step, 'negcontrol').value ?? ''" :disabled="store.readOnly" maxlength="1" aria-label="Negatieve controle" @change="metadata(metadataField(step, 'negcontrol'), $event)"></td>
                                <td class="control-column"><input v-if="metadataField(step, 'blankcontrol')" v-sync-value="metadataField(step, 'blankcontrol').value ?? ''" :disabled="store.readOnly" maxlength="1" aria-label="Blanco controle" @change="metadata(metadataField(step, 'blankcontrol'), $event)"></td>
                                <td class="tht-column">
                                    <template v-if="metadataField(step, 'tht')">
                                        <ConfirmationDateInput
                                            v-if="metadataField(step, 'tht').kind !== 'material'"
                                            :value="metadataField(step, 'tht').value"
                                            :tone="thtTone(metadataField(step, 'tht'))"
                                            :disabled="store.readOnly || !metadataField(step, 'tht').available"
                                            :label="`THT ${step.name}`"
                                            @commit="saveDate(metadataField(step, 'tht'), $event)"
                                        />
                                        <input
                                            v-else
                                            class="material-value"
                                            :class="thtTone(metadataField(step, 'tht'))"
                                            v-sync-value="metadataField(step, 'tht').value ?? ''"
                                            :disabled="store.readOnly || !metadataField(step, 'tht').available"
                                            :placeholder="metadataField(step, 'tht').acceptable_range || ''"
                                            :aria-label="`THT ${step.name}`"
                                            @change="metadata(metadataField(step, 'tht'), $event)"
                                        >
                                        <small v-if="metadataField(step, 'tht').out_of_date_text" class="assurance-note">{{ metadataField(step, 'tht').out_of_date_text }}</small>
                                        <button v-if="metadataField(step, 'tht').requires_explanation || metadataField(step, 'tht').explanation" class="assurance-explanation-button" type="button" title="Uitleg" :aria-label="`Uitleg THT ${step.name}`" @click="store.openAssuranceExplanation(metadataField(step, 'tht'))"><MessageSquare :size="14" /></button>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="!(store.data?.config.steps ?? []).length"><td :colspan="(store.selectedEvaluation?.contenders?.length ?? 0) + 7" class="empty-state">Geen bevestigingsmedia geconfigureerd.</td></tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</template>

<style scoped>
.confirmation-racetrack { display:grid; gap:14px; min-width:0; }
.confirmation-grid-scroll { overflow:auto; border:1px solid var(--line); background:var(--surface); }
.confirmation-grid { width:100%; table-layout:fixed; border-collapse:collapse; text-align:left; font-size:13px; }
.confirmation-grid th,.confirmation-grid td { padding:6px; border-right:1px solid var(--line); border-bottom:1px solid var(--line); vertical-align:middle; }
.confirmation-grid thead th { position:sticky; top:0; background:var(--surface-alt); color:var(--muted); font-size:12px; font-weight:700; }
.confirmation-grid .medium-column,.confirmation-grid tbody th { width:150px; }
.confirmation-grid .date-column { width:136px; }
.confirmation-grid .control-column { width:54px; }
.confirmation-grid .tht-column { width:180px; }
.confirmation-grid thead .colony-heading { width:88px; white-space:nowrap; }
.colony-heading span { margin-right:4px; }
.colony-remove { display:inline-grid; place-items:center; width:22px; height:22px; padding:0; border:1px solid transparent; border-radius:2px; background:transparent; color:var(--muted); vertical-align:middle; cursor:pointer; }
.colony-remove:hover:not(:disabled),.colony-remove:focus-visible { border-color:#bf685d; background:#fff1ed; color:#903e32; }
.colony-remove:disabled { opacity:.4; cursor:default; }
.confirmation-grid tbody th { position:sticky; left:0; z-index:1; background:var(--surface-alt); color:var(--ink); font-size:13px; }
.confirmation-grid tbody th strong { margin-left:4px; color:var(--accent); }
.confirmation-grid input { width:100%; min-width:0; min-height:30px; border:1px solid var(--control-border); border-radius:2px; background:var(--surface); color:var(--ink); padding:3px 6px; font:inherit; }
.confirmation-grid input:disabled { background:var(--surface-alt); color:var(--muted); }
.confirmation-grid .control-column input { text-align:center; }
.confirmation-grid .tht-column { position:relative; }
.confirmation-grid .tht-column input.warning { border-color:#bf685d; background:#fff1ed; }
.confirmation-grid .tht-column input.explained { border-color:#c78a2c; background:#fff2d9; }
.confirmation-grid .assurance-note { display:block; overflow:hidden; color:#903e32; font-size:11px; text-overflow:ellipsis; white-space:nowrap; }
.confirmation-grid .assurance-explanation-button { display:inline-grid; place-items:center; width:26px; height:26px; margin-top:4px; border:1px solid var(--control-border); border-radius:2px; background:var(--surface); color:var(--accent); cursor:pointer; }
.confirmation-grid .answer-cell input { text-align:center; font-size:17px; font-weight:700; padding:3px 0; }
.confirmation-grid .answer-cell input:focus { outline:2px solid var(--accent); outline-offset:-2px; }
.confirmation-grid .answer-cell input.answer-matching { border-color:#4d9468; background:#e7f5ec; color:#1f6a3e; }
.confirmation-grid .answer-cell input.answer-mismatching { border-color:#bf685d; background:#fae9e6; color:#963e34; }
.confirmation-grid .answer-cell input.answer-ambiguous { border-color:#c78a2c; background:#fff2d9; color:#8a5708; }
</style>