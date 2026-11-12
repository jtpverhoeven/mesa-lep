<script setup>
import { computed, reactive, watch } from 'vue';
import { Save, X } from '@lucide/vue';
import Dialog from 'openvue/dialog';
import { createAppDialogPassThrough } from '../dialogPassThrough';
import { dilutionProblem, toObject, toText } from '../dilutions';
import DilutionSelector from './DilutionSelector.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    assay: { type: Object, default: null },
    defaults: { type: Object, default: null },
    referenceSources: { type: Array, default: () => [] },
});
const emit = defineEmits(['cancel', 'save']);
const form = reactive({ dillutionsText: '', replicates: 0, referenceText: '', referenceSource: '', referenceScope: '' });
const errors = reactive({ reference: '' });
const dialogPassThrough = createAppDialogPassThrough({ width: '620px', titleId: 'roaming-title' });

const dilutionProblemText = computed(() => (props.assay?.dillution ? dilutionProblem(form.dillutionsText) : ''));

function reset() {
    const defaults = props.defaults ?? {};
    Object.assign(form, {
        dillutionsText: toText(defaults.dillutions ?? (props.assay?.dillution ? { '-1': '0.1', '-2': '0.01', '-3': '0.001', '-4': '0.0001' } : { 0: 1 })),
        replicates: Number(defaults.replicates ?? 0),
        referenceText: toText(defaults.reference),
        referenceSource: defaults.reference_source ?? '',
        referenceScope: defaults.reference_scope ?? '',
    });
    errors.reference = '';
}

function save() {
    if (dilutionProblemText.value) return;
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
    <Dialog :visible="open" modal :draggable="false" :dismissable-mask="true" :close-on-escape="false" :closable="false" :block-scroll="true" :pt="dialogPassThrough" @update:visible="emit('cancel')">
        <template #header>
            <div><small class="block text-[10px] text-[var(--muted)]">Losse analyse</small><h3 id="roaming-title" class="m-0 text-[15px]">{{ assay?.name }}</h3></div>
            <button class="icon-button" type="button" title="Sluiten" @click="emit('cancel')"><X :size="16" /></button>
        </template>
        <div class="form-grid p-[14px]">
            <DilutionSelector v-if="assay?.dillution" v-model="form.dillutionsText" :problem="dilutionProblemText" input-id="roaming-dillutions" />
            <div v-if="assay?.replicates" class="field"><label for="roaming-replicates">Replica's</label><input id="roaming-replicates" v-model.number="form.replicates" type="number" min="0"></div>
            <div class="field"><label for="roaming-source">Referentiebron</label><select id="roaming-source" v-model="form.referenceSource"><option value="">Geen bron</option><option v-for="source in referenceSources" :key="source.id" :value="source.id">{{ source.name }}</option></select></div>
            <div class="field wide"><label for="roaming-reference">Referentiewaarden</label><textarea id="roaming-reference" v-model="form.referenceText" rows="4" placeholder="ref_naam=waarde"></textarea><small>Een instelling per regel, als naam=waarde.</small><span v-if="errors.reference" class="error-text">{{ errors.reference }}</span></div>
        </div>
        <template #footer><button class="button" type="button" @click="emit('cancel')">Annuleren</button><button class="button primary" type="button" :disabled="Boolean(dilutionProblemText)" @click="save"><Save :size="15" />Instellingen toepassen</button></template>
    </Dialog>
</template>