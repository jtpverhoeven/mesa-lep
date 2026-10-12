<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Filter, LoaderCircle, Minus, Plus, Save } from '@lucide/vue';
import InputNumber from 'openvue/inputnumber';
import InputText from 'openvue/inputtext';
import MultiSelect from 'openvue/multiselect';
import Select from 'openvue/select';
import SelectButton from 'openvue/selectbutton';
import ToggleSwitch from 'openvue/toggleswitch';

const props = defineProps({
    initial: { type: Object, required: true },
    options: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
    actionUrl: { type: String, required: true },
    cancelUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    updating: { type: Boolean, default: false },
});

function parseCondition(value) {
    try {
        const parsed = typeof value === 'string' ? JSON.parse(value) : value;
        return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
    } catch {
        return {};
    }
}

const condition = parseCondition(props.initial.event_data);
const draft = reactive({
    group: String(props.initial.event_group ?? '1'),
    event: props.initial.event ?? 'P',
    type: condition.type ?? null,
    inspect: condition.inspect ?? null,
    value: String(condition.inspect_value ?? ''),
    negative: String(condition.negative) === '1',
    label: props.initial.print_label ? String(props.initial.print_label) : null,
    printer: props.initial.print_to ? String(props.initial.print_to) : null,
    copies: Number(props.initial.copies ?? 1),
});
const assayIds = ref(draft.type === 'assayIs' ? draft.value.split(',').map((id) => id.trim()).filter(Boolean) : []);
const submitting = ref(false);
const groups = Object.entries(props.options.groups).map(([id, name]) => ({ id, name }));
const choices = Object.fromEntries(['labelDesigns', 'printers', 'clients', 'procedures', 'assays', 'assayTypes'].map((key) => [
    key, props.options[key].map((option) => ({ ...option, id: String(option.id) })),
]));
choices.assays = choices.assays.map((assay) => ({ ...assay, selectionLabel: `${assay.name} (#${assay.id})` }));
const sampleFields = props.options.sampleFields.map((field) => ({ id: field.name, name: field.alias || field.name }));
const sampleTypes = Object.entries(props.options.sampleTypes).map(([id, name]) => ({ id, name }));
const conditionTypes = computed(() => Object.entries(props.options.conditions[draft.group] ?? {}).map(([id, name]) => ({ id, name })));
const modes = [{ id: 'P', name: 'Altijd afdrukken' }, { id: 'L', name: 'Voorwaardelijk' }];
const valueOptions = computed(() => ({ sClient: choices.clients, mMethod: choices.procedures, mType: sampleTypes, assayTypeIs: choices.assayTypes }[draft.type] ?? []));
const valueLabel = computed(() => ({ sClient: 'Opdrachtgever', mMethod: 'Monsternameprocedure', mType: 'Monstertype', assayTypeIs: 'Analysetype' }[draft.type] ?? 'Waarde'));
const inspectValue = computed(() => draft.type === 'assayIs' ? assayIds.value.join(',') : draft.value);
const canSave = computed(() => {
    if (!choices.labelDesigns.some((option) => option.id === draft.label) || !choices.printers.some((option) => option.id === draft.printer)) return false;
    if (!Number.isInteger(draft.copies) || draft.copies < 1 || draft.copies > props.options.maxCopies) return false;
    if (draft.event === 'P') return groups.some((group) => group.id === draft.group);
    if (!conditionTypes.value.some((type) => type.id === draft.type)) return false;
    if (draft.type === 'sField') return sampleFields.some((field) => field.id === draft.inspect);
    if (draft.type === 'assayIs') return assayIds.value.length > 0 && assayIds.value.every((id) => choices.assays.some((assay) => assay.id === id));
    return valueOptions.value.some((option) => option.id === draft.value);
});

watch(() => draft.group, () => {
    draft.type = null;
    if (draft.group === '3') draft.event = 'P';
});

watch(() => draft.type, () => {
    draft.inspect = null;
    draft.value = '';
    assayIds.value = [];
});

function error(key) {
    return props.errors[key]?.[0] ?? '';
}

function submit(event) {
    if (!canSave.value || submitting.value) {
        event.preventDefault();
        return;
    }

    submitting.value = true;
}
</script>

<template>
    <form class="label-rule-form max-w-5xl" method="POST" :action="actionUrl" @submit="submit">
        <input type="hidden" name="_token" :value="csrf">
        <input v-if="updating" type="hidden" name="_method" value="PUT">
        <input type="hidden" name="event_group" :value="draft.group">
        <input type="hidden" name="event" :value="draft.event">
        <input type="hidden" name="print_label" :value="draft.label ?? ''">
        <input type="hidden" name="print_to" :value="draft.printer ?? ''">
        <input type="hidden" name="copies" :value="draft.copies ?? ''">

        <fieldset class="form-section">
            <legend>Printmoment</legend>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div class="field">
                    <label id="label-event-group-label">Printmoment</label>
                    <Select v-model="draft.group" :options="groups" option-label="name" option-value="id" aria-labelledby="label-event-group-label" :invalid="Boolean(error('event_group'))" aria-describedby="label-event-group-error" append-to="self" fluid />
                    <small v-if="error('event_group')" id="label-event-group-error" class="error-text">{{ error('event_group') }}</small>
                </div>
                <div class="field">
                    <label id="label-event-mode-label">Regeltype</label>
                    <SelectButton v-if="draft.group !== '3'" v-model="draft.event" :options="modes" option-label="name" option-value="id" :allow-empty="false" aria-labelledby="label-event-mode-label" :invalid="Boolean(error('event'))" fluid>
                        <template #option="{ option }"><Filter v-if="option.id === 'L'" :size="14" aria-hidden="true" /><span>{{ option.name }}</span></template>
                    </SelectButton>
                    <span v-else class="flex min-h-9 items-center">Altijd afdrukken</span>
                    <small v-if="error('event')" class="error-text">{{ error('event') }}</small>
                </div>
            </div>
        </fieldset>

        <fieldset v-if="draft.event === 'L'" class="form-section">
            <legend>Voorwaarde</legend>
            <input type="hidden" name="event_data[type]" :value="draft.type ?? ''">
            <input type="hidden" name="event_data[inspect_value]" :value="inspectValue">
            <input type="hidden" name="event_data[negative]" :value="draft.negative ? '1' : '0'">
            <input v-if="draft.type === 'sField'" type="hidden" name="event_data[inspect]" :value="draft.inspect ?? ''">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div class="field md:col-span-2">
                    <label id="label-event-condition-label">Voorwaarde</label>
                    <Select v-model="draft.type" :options="conditionTypes" option-label="name" option-value="id" placeholder="Kies een voorwaarde" aria-labelledby="label-event-condition-label" :invalid="Boolean(error('event_data.type'))" aria-describedby="label-event-condition-error" append-to="self" fluid />
                    <small v-if="error('event_data.type')" id="label-event-condition-error" class="error-text">{{ error('event_data.type') }}</small>
                    <small v-if="error('event_data')" class="error-text">{{ error('event_data') }}</small>
                </div>
                <template v-if="draft.type === 'sField'">
                    <div class="field">
                        <label id="label-event-field-label">Monsterveld</label>
                        <Select v-model="draft.inspect" :options="sampleFields" option-label="name" option-value="id" placeholder="Kies een monsterveld" filter filter-placeholder="Zoek monsterveld" aria-labelledby="label-event-field-label" :invalid="Boolean(error('event_data.inspect'))" append-to="self" fluid />
                        <small v-if="error('event_data.inspect')" class="error-text">{{ error('event_data.inspect') }}</small>
                    </div>
                    <div class="field">
                        <label for="label-event-field-value">Vergelijkingswaarde</label>
                        <InputText id="label-event-field-value" v-model="draft.value" :invalid="Boolean(error('event_data.inspect_value'))" maxlength="65535" fluid />
                    </div>
                </template>
                <div v-else-if="draft.type === 'assayIs'" class="field md:col-span-2">
                    <label for="label-event-assays">Analyses</label>
                    <MultiSelect v-model="assayIds" input-id="label-event-assays" :options="choices.assays" option-label="selectionLabel" option-value="id" placeholder="Kies analyses" display="chip" :show-toggle-all="false" :virtual-scroller-options="{ itemSize: 52 }" filter filter-placeholder="Zoek analyse" :invalid="Boolean(error('event_data.inspect_value'))" append-to="self" fluid>
                        <template #option="{ option }">
                            <span class="flex min-w-0 grow items-center gap-3">
                                <span class="line-clamp-2 min-w-0 grow" :title="option.name">{{ option.name }}</span>
                                <span class="muted shrink-0 tabular-nums">#{{ option.id }}</span>
                            </span>
                        </template>
                    </MultiSelect>
                </div>
                <div v-else-if="draft.type" class="field md:col-span-2">
                    <label id="label-event-value-label">{{ valueLabel }}</label>
                    <Select v-model="draft.value" :options="valueOptions" option-label="name" option-value="id" :placeholder="`Kies ${valueLabel.toLocaleLowerCase()}`" filter :filter-placeholder="`Zoek ${valueLabel.toLocaleLowerCase()}`" aria-labelledby="label-event-value-label" :invalid="Boolean(error('event_data.inspect_value'))" append-to="self" fluid />
                </div>
                <small v-if="error('event_data.inspect_value')" class="error-text md:col-span-2">{{ error('event_data.inspect_value') }}</small>
                <div class="flex items-center gap-3 md:col-span-2">
                    <ToggleSwitch v-model="draft.negative" input-id="label-event-negative" :invalid="Boolean(error('event_data.negative'))" />
                    <label for="label-event-negative" class="font-semibold">Voorwaarde omkeren</label>
                    <span class="muted">{{ draft.negative ? 'Komt niet overeen' : 'Komt overeen' }}</span>
                </div>
                <small v-if="error('event_data.negative')" class="error-text md:col-span-2">{{ error('event_data.negative') }}</small>
            </div>
        </fieldset>

        <fieldset class="form-section">
            <legend>Afdrukken</legend>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div class="field">
                    <label id="label-event-design-label">Labelontwerp</label>
                    <Select v-model="draft.label" :options="choices.labelDesigns" option-label="name" option-value="id" placeholder="Kies een labelontwerp" filter filter-placeholder="Zoek labelontwerp" aria-labelledby="label-event-design-label" :invalid="Boolean(error('print_label'))" append-to="self" fluid />
                    <small v-if="!choices.labelDesigns.length" class="error-text">Geen labelontwerpen beschikbaar.</small>
                    <small v-if="error('print_label')" class="error-text">{{ error('print_label') }}</small>
                </div>
                <div class="field">
                    <label id="label-event-printer-label">Printer</label>
                    <Select v-model="draft.printer" :options="choices.printers" option-label="name" option-value="id" placeholder="Kies een printer" filter filter-placeholder="Zoek printer" aria-labelledby="label-event-printer-label" :invalid="Boolean(error('print_to'))" append-to="self" fluid />
                    <small v-if="!choices.printers.length" class="error-text">Geen printers beschikbaar.</small>
                    <small v-if="error('print_to')" class="error-text">{{ error('print_to') }}</small>
                </div>
                <div class="field max-w-48">
                    <label for="label-event-copies">Aantal exemplaren</label>
                    <InputNumber v-model="draft.copies" input-id="label-event-copies" :min="1" :max="options.maxCopies" :use-grouping="false" :invalid="Boolean(error('copies'))" :pt="{ incrementButton: { 'aria-label': 'Meer exemplaren', title: 'Meer exemplaren' }, decrementButton: { 'aria-label': 'Minder exemplaren', title: 'Minder exemplaren' } }" show-buttons fluid>
                        <template #incrementbuttonicon><Plus :size="12" aria-hidden="true" /></template>
                        <template #decrementbuttonicon><Minus :size="12" aria-hidden="true" /></template>
                    </InputNumber>
                    <small v-if="error('copies')" class="error-text">{{ error('copies') }}</small>
                </div>
            </div>
        </fieldset>
        <div class="form-actions flex-wrap">
            <button class="button primary" type="submit" :disabled="!canSave || submitting">
                <LoaderCircle v-if="submitting" :size="16" class="spin" aria-hidden="true" /><Save v-else :size="16" aria-hidden="true" />
                {{ updating ? 'Wijzigingen opslaan' : 'Labelregel aanmaken' }}
            </button>
            <a class="button" :href="cancelUrl">Annuleren</a>
        </div>
    </form>
</template>

<style scoped>
.label-rule-form { --p-primary-color:var(--accent); --p-select-background:var(--surface); --p-select-color:var(--ink); --p-select-border-color:var(--line); --p-select-focus-border-color:var(--accent); --p-select-border-radius:2px; --p-select-overlay-background:var(--surface); --p-select-overlay-color:var(--ink); --p-select-option-selected-background:var(--accent-faint); --p-select-option-selected-color:var(--accent); --p-multiselect-background:var(--surface); --p-multiselect-color:var(--ink); --p-multiselect-border-color:var(--line); --p-multiselect-border-radius:2px; --p-multiselect-overlay-background:var(--surface); --p-multiselect-overlay-color:var(--ink); --p-multiselect-option-selected-background:var(--accent-faint); --p-multiselect-option-selected-color:var(--accent); --p-togglebutton-border-radius:2px; --p-togglebutton-background:var(--surface); --p-togglebutton-color:var(--ink); --p-togglebutton-checked-background:var(--accent-faint); --p-togglebutton-checked-color:var(--accent); --p-togglebutton-content-checked-background:var(--accent-faint); --p-toggleswitch-checked-background:var(--accent); }
.label-rule-form :deep(.p-select),.label-rule-form :deep(.p-multiselect),.label-rule-form :deep(.p-togglebutton),.label-rule-form :deep(.p-select-overlay),.label-rule-form :deep(.p-multiselect-overlay) { min-width:0; font:inherit; }
.label-rule-form :deep(.p-select-label),.label-rule-form :deep(.p-multiselect-label) { padding:9px 10px; font:inherit; }
.label-rule-form :deep(.p-select-option),.label-rule-form :deep(.p-multiselect-option) { min-height:36px; white-space:normal; overflow-wrap:anywhere; }
.label-rule-form :deep(.p-multiselect-option) { height:52px; min-height:52px; }
.label-rule-form :deep(.p-togglebutton) { min-height:37px; }
.label-rule-form :deep(.p-togglebutton-content) { flex-wrap:wrap; gap:6px; }
.label-rule-form :deep(.p-multiselect-label),.label-rule-form :deep(.p-multiselect-chip-items) { flex-wrap:wrap; }
.label-rule-form :deep(.p-chip) { max-width:100%; border-radius:2px; font:inherit; }
.label-rule-form :deep(.p-chip-label) { overflow:hidden; text-overflow:ellipsis; }
.label-rule-form :deep(.p-inputnumber-button) { width:28px; color:var(--accent); background:var(--surface-alt); border-color:var(--line); }
</style>