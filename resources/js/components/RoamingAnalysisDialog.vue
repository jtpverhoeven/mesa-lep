<script setup>
import { reactive, ref, watch } from 'vue';
import { Code2, ListChecks, Save, X } from '@lucide/vue';
import Dialog from 'openvue/dialog';
import { createAppDialogPassThrough } from '../dialogPassThrough';

const props = defineProps({
    open: { type: Boolean, default: false },
    assay: { type: Object, default: null },
    defaults: { type: Object, default: null },
    referenceSources: { type: Array, default: () => [] },
});
const emit = defineEmits(['cancel', 'save']);
const form = reactive({ dillutionsText: '', replicates: 0, referenceText: '', referenceSource: '', referenceScope: '' });
const errors = reactive({ dillutions: '', reference: '' });
const dilutionMode = ref('picker');
const dilutionOptions = [
    { key: '0', value: '1', label: 'Onverdund' },
    { key: '-1', value: '0.1', label: '-1' },
    { key: '-2', value: '0.01', label: '-2' },
    { key: '-3', value: '0.001', label: '-3' },
    { key: '-4', value: '0.0001', label: '-4' },
    { key: '-5', value: '0.00001', label: '-5' },
    { key: '-6', value: '0.000001', label: '-6' },
    { key: '-7', value: '0.0000001', label: '-7' },
    { key: '-8', value: '0.00000001', label: '-8' },
    { key: '-9', value: '0.000000001', label: '-9' },
    { key: '-10', value: '0.0000000001', label: '-10' },
];
const dialogPassThrough = createAppDialogPassThrough({ width: '620px', titleId: 'roaming-title' });

function toText(value) {
    return Object.entries(value ?? {}).map(([key, setting]) => `${key}=${setting}`).join('\n');
}

function toObject(value) {
    const result = {};
    value.split(/\r?\n/).forEach((line) => {
        const [key, ...rest] = line.split('=');
        if (key?.trim() && rest.length) result[key.trim()] = rest.join('=').trim();
    });
    return result;
}

function selectedDilution(option) {
    return String(toObject(form.dillutionsText)[option.key] ?? '') === option.value;
}

function toggleDilution(option, checked) {
    const dillutions = toObject(form.dillutionsText);
    if (checked) dillutions[option.key] = option.value;
    else delete dillutions[option.key];
    form.dillutionsText = toText(dillutions);
    errors.dillutions = '';
}

function validateDillutions() {
    const invalid = form.dillutionsText.split(/\r?\n/).some((line) => {
        if (!line.trim()) return false;
        const [key, ...rest] = line.split('=');
        return !key?.trim() || rest.length !== 1 || rest[0].trim() === '' || Number.isNaN(Number(rest[0]));
    });
    errors.dillutions = invalid ? 'Gebruik een geldige verdunningscurve met een numerieke waarde per regel.' : '';
    return !invalid;
}

function reset() {
    const defaults = props.defaults ?? {};
    Object.assign(form, {
        dillutionsText: toText(defaults.dillutions ?? (props.assay?.dillution ? { '-1': '0.1', '-2': '0.01', '-3': '0.001', '-4': '0.0001' } : { 0: 1 })),
        replicates: Number(defaults.replicates ?? 0),
        referenceText: toText(defaults.reference),
        referenceSource: defaults.reference_source ?? '',
        referenceScope: defaults.reference_scope ?? '',
    });
    dilutionMode.value = 'picker';
    errors.dillutions = '';
    errors.reference = '';
}

function save() {
    if (!validateDillutions()) return;
    const reference = toObject(form.referenceText);
    if (Object.keys(reference).length && !form.referenceSource) {
        errors.reference = 'Selecteer een referentiebron wanneer referentiewaarden zijn ingevuld.';
        return;
    }
    emit('save', {
        dillutions: toObject(form.dillutionsText),
        replicates: Number(form.replicates || 0),
        reference,
        reference_scope: form.referenceScope,
        reference_source: form.referenceSource ? Number(form.referenceSource) : null,
    });
}

watch(() => [props.open, props.assay?.id, props.defaults], ([open]) => { if (open) reset(); }, { deep: true });
</script>

<template>
    <Dialog :visible="open" modal :draggable="false" :dismissable-mask="true" :close-on-escape="false" :closable="false" :block-scroll="true" :unstyled="true" :pt="dialogPassThrough" @update:visible="emit('cancel')">
        <template #header>
            <div><small class="block text-[10px] text-[var(--muted)]">Losse analyse</small><h3 id="roaming-title" class="m-0 text-[15px]">{{ assay?.name }}</h3></div>
            <button class="icon-button" type="button" title="Sluiten" @click="emit('cancel')"><X :size="16" /></button>
        </template>
        <div class="form-grid p-[14px]">
            <div v-if="assay?.dillution" class="field wide dilution-control">
                <div class="dilution-heading"><label>Verdunningen</label><div class="dilution-mode" role="tablist" aria-label="Invoermethode verdunningen"><button type="button" role="tab" :aria-selected="dilutionMode === 'picker'" title="Interactieve selectie" @click="dilutionMode = 'picker'"><ListChecks :size="15" /></button><button type="button" role="tab" :aria-selected="dilutionMode === 'text'" title="Tekstinvoer" @click="dilutionMode = 'text'"><Code2 :size="15" /></button></div></div>
                <div v-if="dilutionMode === 'picker'" class="dilution-picker"><label v-for="option in dilutionOptions" :key="option.key"><input type="checkbox" :checked="selectedDilution(option)" @change="toggleDilution(option, $event.target.checked)"><span>{{ option.label }}</span></label></div>
                <textarea v-else id="roaming-dillutions" v-model="form.dillutionsText" rows="6" placeholder="-1=0.1" @blur="validateDillutions"></textarea>
                <small>De interactieve selectie en tekstweergave blijven gesynchroniseerd.</small><span v-if="errors.dillutions" class="error-text">{{ errors.dillutions }}</span>
            </div>
            <div v-if="assay?.replicates" class="field"><label for="roaming-replicates">Replica's</label><input id="roaming-replicates" v-model.number="form.replicates" type="number" min="0"></div>
            <div class="field"><label for="roaming-source">Referentiebron</label><select id="roaming-source" v-model="form.referenceSource"><option value="">Geen bron</option><option v-for="source in referenceSources" :key="source.id" :value="source.id">{{ source.name }}</option></select></div>
            <div class="field wide"><label for="roaming-reference">Referentiewaarden</label><textarea id="roaming-reference" v-model="form.referenceText" rows="4" placeholder="ref_naam=waarde"></textarea><small>Een instelling per regel, als naam=waarde.</small><span v-if="errors.reference" class="error-text">{{ errors.reference }}</span></div>
        </div>
        <template #footer><button class="button" type="button" @click="emit('cancel')">Annuleren</button><button class="button primary" type="button" @click="save"><Save :size="15" />Instellingen toepassen</button></template>
    </Dialog>
</template>