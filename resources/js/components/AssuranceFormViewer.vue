<script setup>
import { computed, ref } from 'vue';
import { ChevronLeft, ChevronRight, LoaderCircle, MessageSquare, Printer, RefreshCw, Save } from '@lucide/vue';
import DatePicker from 'openvue/datepicker';
import AppDialog from './AppDialog.vue';

const props = defineProps({
    formDate: { type: String, required: true },
    incompleteForms: { type: Array, default: () => [] },
    endpoints: { type: Object, required: true },
});

const selectedDate = ref(dateFromIso(props.formDate));
const form = ref(null);
const incompleteForms = ref(props.incompleteForms);
const loading = ref(false);
const saving = ref(false);
const error = ref('');
const explanationField = ref(null);
const explanation = ref('');

const sections = computed(() => form.value?.sections ?? {});
const sectionEntries = computed(() => Object.entries(sections.value).sort(([left], [right]) => Number(left) - Number(right)));
const fixedSectionEntries = computed(() => sectionEntries.value.filter(([section]) => Number(section) !== 3));
const dynamicSectionEntries = computed(() => form.value?.dynamic_sections ?? []);
const missingItems = computed(() => (form.value?.missing ?? []).map(missingLabel));
const displaySections = computed(() => fixedSectionEntries.value.map(([section, fields]) => ({
    key: `section-${section}`,
    label: sectionLabel(Number(section)),
    fields,
})));
const selectedDateDisplay = computed(() => legacyDate(selectedDate.value));

function endpoint(name, token, value) {
    return props.endpoints[name].replace(token, encodeURIComponent(value));
}

function dateFromIso(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value ?? '');

    return match ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3])) : null;
}

function dateFromLegacy(value) {
    const match = /^(\d{2})-(\d{2})-(\d{4})$/.exec(value ?? '');

    return match ? new Date(Number(match[3]), Number(match[2]) - 1, Number(match[1])) : null;
}

function isoDate(value) {
    if (!(value instanceof Date) || Number.isNaN(value.getTime())) {
        return '';
    }

    return [value.getFullYear(), String(value.getMonth() + 1).padStart(2, '0'), String(value.getDate()).padStart(2, '0')].join('-');
}

function legacyDate(value) {
    if (!(value instanceof Date) || Number.isNaN(value.getTime())) {
        return '';
    }

    return [String(value.getDate()).padStart(2, '0'), String(value.getMonth() + 1).padStart(2, '0'), value.getFullYear()].join('-');
}

function applyFormState(state) {
    form.value = state;

    if (Array.isArray(state.incomplete_forms)) {
        incompleteForms.value = state.incomplete_forms;
    }
}

function missingLabel(label) {
    const match = /^(Media: |Uitleg media: )(.+)$/.exec(label);

    if (!match) {
        return label;
    }

    const field = form.value?.fields?.find((item) => item.key.replace(/^b3_/, '') === match[2]);

    return field ? `${match[1]}${field.label}` : label;
}

async function request(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            ...(options.body ? { 'Content-Type': 'application/json' } : {}),
            ...(options.headers ?? {}),
        },
    });
    const result = await response.json();

    if (!response.ok) {
        throw new Error(Object.values(result.errors ?? {}).flat().join(' ') || result.message || 'Borgingsformulier kon niet worden opgeslagen.');
    }

    return result.data;
}

async function load(date = selectedDate.value) {
    const formDate = typeof date === 'string' ? date : isoDate(date);

    if (!formDate) {
        return;
    }

    selectedDate.value = dateFromIso(formDate);
    loading.value = true;
    error.value = '';

    try {
        applyFormState(await request(endpoint('show', '__DATE__', formDate)));
        window.history.replaceState({}, '', endpoint('page', '__DATE__', formDate));
    } catch (requestError) {
        error.value = requestError.message;
    } finally {
        loading.value = false;
    }
}

async function createForm() {
    const formDate = isoDate(selectedDate.value);

    if (!formDate) {
        return;
    }

    loading.value = true;
    error.value = '';

    try {
        applyFormState(await request(endpoint('store', '__DATE__', formDate), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
        }));
    } catch (requestError) {
        error.value = requestError.message;
    } finally {
        loading.value = false;
    }
}

function shiftDate(offset) {
    const date = new Date(selectedDate.value);
    date.setDate(date.getDate() + offset);
    load(date);
}

function selectIncomplete(event) {
    if (event.target.value) {
        load(event.target.value);
    }
}

async function saveField(field, value) {
    if (!form.value?.id || saving.value) {
        return;
    }

    const previousValue = field.value;
    field.value = value;
    saving.value = true;
    error.value = '';

    try {
        applyFormState(await request(endpoint('updateField', '__FORM__', form.value.id), {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
            body: JSON.stringify({ field_key: field.key, value }),
        }));
    } catch (requestError) {
        field.value = previousValue;
        error.value = requestError.message;
    } finally {
        saving.value = false;
    }
}

function saveDateField(field, value) {
    saveField(field, legacyDate(value));
}

function toggleNotApplicable(field) {
    saveField(field, field.value === 'nvt' ? '' : 'nvt');
}

function startExplanation(field) {
    explanationField.value = field;
    explanation.value = field.explanation ?? '';
}

async function saveExplanation() {
    if (!form.value?.id || !explanationField.value || saving.value) {
        return;
    }

    saving.value = true;
    error.value = '';

    try {
        applyFormState(await request(endpoint('updateExplanation', '__FORM__', form.value.id), {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
            body: JSON.stringify({ field_key: explanationField.value.key, explanation: explanation.value }),
        }));
        explanationField.value = null;
    } catch (requestError) {
        error.value = requestError.message;
    } finally {
        saving.value = false;
    }
}

function printForm() {
    if (form.value?.id) {
        window.open(endpoint('print', '__FORM__', form.value.id), '_blank', 'noopener');
    }
}

function sectionLabel(section) {
    return { 0: 'Algemeen', 1: 'Uit stoof / aflezen', 2: 'Ophopings- / verdunningsvloeistoffen', 3: 'Media en materiaal' }[section] ?? `Blok ${section}`;
}

load();
</script>

<template>
    <section class="assurance-viewer">
        <div class="assurance-toolbar">
            <div class="assurance-date-control">
                <button class="icon-button" type="button" title="Vorige dag" aria-label="Vorige dag" @click="shiftDate(-1)"><ChevronLeft :size="16" /></button>
                <DatePicker :model-value="selectedDate" date-format="dd-mm-yy" show-icon aria-label="Inzetdatum" @update:model-value="load" />
                <button class="icon-button" type="button" title="Volgende dag" aria-label="Volgende dag" @click="shiftDate(1)"><ChevronRight :size="16" /></button>
            </div>
            <div class="assurance-toolbar-actions">
                <select aria-label="Incomplete borgingsformulieren" @change="selectIncomplete">
                    <option value="">Ontbrekende gegevens</option>
                    <option v-for="item in incompleteForms" :key="item.id" :value="item.form_date">{{ item.display_date }}</option>
                </select>
                <button class="button" type="button" :disabled="loading" title="Opnieuw laden" @click="load()"><RefreshCw :size="15" />Laden</button>
                <button class="button" type="button" :disabled="!form?.id" title="Printen" @click="printForm"><Printer :size="15" />Print</button>
            </div>
        </div>

        <p v-if="error" class="notice error" role="alert">{{ error }}</p>
        <div v-if="loading" class="sample-loading" role="status"><LoaderCircle class="spin" :size="18" />Borgingsformulier laden...</div>
        <div v-else-if="!form?.exists" class="assurance-empty">
            <h2>Geen borgingsformulier</h2>
            <p>{{ form?.message ?? `Voor ${selectedDateDisplay} bestaat nog geen actief formulier.` }}</p>
            <button class="button primary" type="button" @click="createForm"><Save :size="15" />Formulier aanmaken</button>
        </div>
        <template v-else>
            <div class="assurance-heading">
                <div><span class="eyebrow">Inzetdatum</span><h2>{{ form.display_date }}</h2></div>
                <strong :class="form.is_complete ? 'assurance-complete' : 'assurance-incomplete'">{{ form.is_complete ? 'Compleet' : 'Ontbrekende gegevens' }}</strong>
            </div>
            <div v-if="form.missing?.length" class="assurance-missing notice error">
                <strong>Dit formulier mist de volgende gegevens</strong>
                <span>{{ missingItems.join(', ') }}</span>
            </div>
            <section v-for="section in displaySections" :key="section.key" class="assurance-section">
                <h2>{{ section.label }}</h2>
                <div class="assurance-field-grid">
                    <article v-for="field in section.fields" :key="field.key" class="assurance-field" :class="{ 'field-warning': field.out_of_specification || field.out_of_date_here, 'field-explained': field.explanation && (field.out_of_specification || field.out_of_date_here) }">
                        <label :for="`assurance-${field.key}`">{{ field.label }}</label>
                        <div class="assurance-input-row">
                            <select v-if="field.kind === 'user'" :id="`assurance-${field.key}`" :value="field.value" @change="saveField(field, $event.target.value)">
                                <option value=""></option>
                                <option v-for="(name, id) in form.users" :key="id" :value="id">{{ name }}</option>
                            </select>
                            <DatePicker v-else-if="field.kind === 'date'" :id="`assurance-${field.key}`" :model-value="dateFromLegacy(field.value)" :disabled="field.value === 'nvt'" :placeholder="field.value === 'nvt' ? 'Niet van toepassing' : 'dd-mm-jjjj'" date-format="dd-mm-yy" :show-icon="field.value !== 'nvt'" @update:model-value="saveDateField(field, $event)" />
                            <input v-else-if="field.kind === 'material'" :id="`assurance-${field.key}`" :value="field.value" :placeholder="field.acceptable_range || ''" @change="saveField(field, $event.target.value)">
                            <button v-if="field.kind === 'date'" class="button date-not-applicable" :class="{ active: field.value === 'nvt' }" type="button" :aria-pressed="field.value === 'nvt'" @click.stop="toggleNotApplicable(field)">{{ field.value === 'nvt' ? 'Datum gebruiken' : 'NVT' }}</button>
                            <button v-if="field.requires_explanation || field.explanation" class="icon-button" type="button" title="Uitleg" :aria-label="`Uitleg ${field.label}`" @click.stop="startExplanation(field)"><MessageSquare :size="15" /></button>
                        </div>
                        <small v-if="field.out_of_date_text" class="assurance-warning-text">Gebruikt na THT bij monster(s): {{ field.out_of_date_text }}</small>
                        <small v-if="field.explanation && (field.out_of_specification || field.out_of_date_here)" class="assurance-explanation">Uitleg: {{ field.explanation }}</small>
                    </article>
                </div>
            </section>
            <section v-for="section in dynamicSectionEntries" :key="section.key" class="assurance-section">
                <h2>{{ section.label }}</h2>
                <div class="assurance-media-table">
                    <div v-for="row in section.rows" :key="row.key" class="assurance-media-row">
                        <div class="assurance-media-name">{{ row.parent?.label ?? row.media_name }}</div>
                        <div class="assurance-media-value">
                            <template v-if="row.parent">
                                <div class="assurance-input-row" :class="{ 'field-warning': row.parent.out_of_specification || row.parent.out_of_date_here, 'field-explained': row.parent.explanation && (row.parent.out_of_specification || row.parent.out_of_date_here) }">
                                    <DatePicker v-if="row.parent.kind === 'date'" :id="`assurance-${row.parent.key}`" :model-value="dateFromLegacy(row.parent.value)" :disabled="row.parent.value === 'nvt'" :placeholder="row.parent.value === 'nvt' ? 'Niet van toepassing' : 'dd-mm-jjjj'" date-format="dd-mm-yy" :show-icon="row.parent.value !== 'nvt'" @update:model-value="saveDateField(row.parent, $event)" />
                                    <input v-else-if="row.parent.kind === 'material'" :id="`assurance-${row.parent.key}`" :value="row.parent.value" :placeholder="row.parent.acceptable_range || ''" @change="saveField(row.parent, $event.target.value)">
                                    <button v-if="row.parent.kind === 'date'" class="button date-not-applicable" :class="{ active: row.parent.value === 'nvt' }" type="button" :aria-pressed="row.parent.value === 'nvt'" @click.stop="toggleNotApplicable(row.parent)">{{ row.parent.value === 'nvt' ? 'Datum gebruiken' : 'NVT' }}</button>
                                    <button v-if="row.parent.requires_explanation || row.parent.explanation" class="icon-button" type="button" title="Uitleg" :aria-label="`Uitleg ${row.parent.label}`" @click.stop="startExplanation(row.parent)"><MessageSquare :size="15" /></button>
                                </div>
                                <small v-if="row.parent.out_of_date_text" class="assurance-warning-text">Gebruikt na THT bij monster(s): {{ row.parent.out_of_date_text }}</small>
                                <small v-if="row.parent.explanation && (row.parent.out_of_specification || row.parent.out_of_date_here)" class="assurance-explanation">Uitleg: {{ row.parent.explanation }}</small>
                            </template>
                        </div>
                        <div class="assurance-supplement-names">
                            <span v-for="supplement in row.supplements" :key="supplement.field.key">{{ supplement.name }}</span>
                        </div>
                        <div class="assurance-supplement-values">
                            <div v-for="supplement in row.supplements" :key="supplement.field.key" class="assurance-supplement-value">
                                <div class="assurance-input-row" :class="{ 'field-warning': supplement.field.out_of_specification || supplement.field.out_of_date_here, 'field-explained': supplement.field.explanation && (supplement.field.out_of_specification || supplement.field.out_of_date_here) }">
                                    <DatePicker :id="`assurance-${supplement.field.key}`" :model-value="dateFromLegacy(supplement.field.value)" :disabled="supplement.field.value === 'nvt'" :placeholder="supplement.field.value === 'nvt' ? 'Niet van toepassing' : 'dd-mm-jjjj'" date-format="dd-mm-yy" :show-icon="supplement.field.value !== 'nvt'" @update:model-value="saveDateField(supplement.field, $event)" />
                                    <button class="button date-not-applicable" :class="{ active: supplement.field.value === 'nvt' }" type="button" :aria-pressed="supplement.field.value === 'nvt'" @click.stop="toggleNotApplicable(supplement.field)">{{ supplement.field.value === 'nvt' ? 'Datum gebruiken' : 'NVT' }}</button>
                                    <button v-if="supplement.field.requires_explanation || supplement.field.explanation" class="icon-button" type="button" title="Uitleg" :aria-label="`Uitleg ${supplement.name}`" @click.stop="startExplanation(supplement.field)"><MessageSquare :size="15" /></button>
                                </div>
                                <small v-if="supplement.field.out_of_date_text" class="assurance-warning-text">Gebruikt na THT bij monster(s): {{ supplement.field.out_of_date_text }}</small>
                                <small v-if="supplement.field.explanation && (supplement.field.out_of_specification || supplement.field.out_of_date_here)" class="assurance-explanation">Uitleg: {{ supplement.field.explanation }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </template>
        <AppDialog :visible="explanationField !== null" :title="explanationField ? `Uitleg: ${explanationField.label}` : 'Uitleg'" @update:visible="explanationField = null">
            <p v-if="explanationField?.out_of_date_text" class="assurance-dialog-warning">Gebruikt na THT bij monster(s): {{ explanationField.out_of_date_text }}</p>
            <textarea v-model="explanation" class="assurance-explanation-editor" rows="7" autofocus aria-label="Uitleg"></textarea>
            <template #footer><button class="button" type="button" @click="explanationField = null">Annuleren</button><button class="button primary" type="button" :disabled="saving" @click="saveExplanation"><Save :size="15" />Opslaan</button></template>
        </AppDialog>
    </section>
</template>

<style scoped>
.assurance-viewer { display:grid; gap:16px; max-width:1320px; }
.assurance-toolbar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; padding:6px 8px; border:1px solid var(--line); background:var(--surface); }
.assurance-date-control,.assurance-toolbar-actions { display:flex; align-items:center; gap:7px; flex-wrap:wrap; }
.assurance-date-control :deep(.p-datepicker),.assurance-toolbar-actions select { min-height:32px; border:1px solid #9eabb2; border-radius:2px; background:var(--surface); color:var(--ink); font:inherit; }
.assurance-date-control :deep(.p-datepicker) { padding:0; }
.assurance-date-control :deep(.p-datepicker-input) { width:108px; min-height:30px; padding:5px 8px; border:0; background:transparent; color:var(--ink); font:inherit; }
.assurance-heading { display:flex; align-items:end; justify-content:space-between; gap:12px; border-bottom:1px solid var(--line); padding-bottom:10px; }
.assurance-heading h2 { margin:4px 0 0; font-size:18px; }
.assurance-complete,.assurance-incomplete { padding:5px 8px; border:1px solid #a7c8b2; background:#edf6ef; color:#24573d; font-size:11px; }
.assurance-incomplete { border-color:#d9aba3; background:#fff1ed; color:#903e32; }
.assurance-missing { display:grid; gap:5px; margin:0; }
.assurance-section { border:1px solid var(--line); background:var(--surface); }
.assurance-section h2 { margin:0; padding:7px 9px; border-bottom:1px solid var(--line); background:var(--surface-alt); font-size:13px; }
.assurance-media-table { display:grid; }
.assurance-media-row { display:grid; grid-template-columns: minmax(140px,1fr) minmax(170px,1fr) minmax(170px,1fr) minmax(170px,1fr); border-bottom:1px solid var(--line); }
.assurance-media-row:last-child { border-bottom:0; }
.assurance-media-row > div { min-width:0; padding:8px 9px; border-right:1px solid var(--line); }
.assurance-media-row > div:last-child { border-right:0; }
.assurance-media-name,.assurance-supplement-names { display:grid; align-content:start; gap:5px; font-weight:600; }
.assurance-supplement-values { display:grid; align-content:start; gap:5px; }
.assurance-supplement-value { min-width:0; }
.assurance-supplement-names span { min-height:32px; }
.assurance-field-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0; }
.assurance-field { display:grid; gap:5px; min-width:0; padding:8px 9px; border-right:1px solid var(--line); border-bottom:1px solid var(--line); }
.assurance-field:nth-child(2n) { border-right:0; }
.assurance-field > label,.assurance-explanation-panel > label { font-weight:600; }
.assurance-input-row { display:grid; grid-template-columns:minmax(0,1fr) auto auto; align-items:center; gap:6px; }
.assurance-input-row input,.assurance-input-row select,.assurance-input-row :deep(.p-datepicker),.assurance-explanation-panel textarea { width:100%; min-width:0; min-height:32px; border:1px solid #9eabb2; border-radius:2px; background:var(--surface); color:var(--ink); padding:6px 8px; font:inherit; }
.assurance-input-row :deep(.p-datepicker) { padding:0; }
.assurance-input-row :deep(.p-datepicker-input) { width:100%; min-width:0; min-height:30px; padding:5px 8px; border:0; background:transparent; color:var(--ink); font:inherit; }
.assurance-input-row .icon-button { margin:0; }
.date-not-applicable { width:108px; min-height:32px; padding:5px 8px; }
.date-not-applicable.active { border-color:var(--accent); background:var(--accent-faint); color:var(--accent); }
.assurance-input-row :deep(.p-disabled) { opacity:1; background:var(--surface-alt); }
.assurance-field.field-warning .assurance-input-row input,.assurance-field.field-warning .assurance-input-row select,.assurance-field.field-warning .assurance-input-row :deep(.p-datepicker),.assurance-input-row.field-warning input,.assurance-input-row.field-warning select,.assurance-input-row.field-warning :deep(.p-datepicker) { border-color:#bf685d; background:#fff1ed; }
.assurance-field.field-explained .assurance-input-row input,.assurance-field.field-explained .assurance-input-row select,.assurance-field.field-explained .assurance-input-row :deep(.p-datepicker),.assurance-input-row.field-explained input,.assurance-input-row.field-explained select,.assurance-input-row.field-explained :deep(.p-datepicker) { border-color:#c78a2c; background:#fff2d9; }
.assurance-warning-text,.assurance-explanation { color:#903e32; font-size:11px; }
.assurance-explanation { color:#8a5708; }
.assurance-empty { display:grid; gap:8px; place-items:start; padding:18px; border:1px solid var(--line); background:var(--surface); }
.assurance-empty h2,.assurance-empty p { margin:0; }
.assurance-explanation-editor { width:100%; min-height:140px; resize:vertical; border:1px solid #9eabb2; border-radius:2px; padding:8px; background:var(--surface); color:var(--ink); font:inherit; }
.assurance-dialog-warning { margin:0 0 10px; color:#903e32; font-size:12px; }
@media(max-width:700px) { .assurance-field-grid { grid-template-columns:1fr; } .assurance-field,.assurance-field:nth-child(2n) { border-right:0; } .assurance-heading { align-items:start; flex-direction:column; } }
@media(max-width:900px) { .assurance-media-row { grid-template-columns:repeat(2,minmax(0,1fr)); } .assurance-media-row > div:nth-child(2) { border-right:0; } .assurance-media-row > div:nth-child(3),.assurance-media-row > div:nth-child(4) { border-top:1px solid var(--line); } }
</style>