<script setup>
import { nextTick, onMounted, ref } from 'vue';
import { BriefcaseBusiness, Building2, FlaskConical, LoaderCircle, Lock, Paperclip, Save, Tag, Unlock } from '@lucide/vue';
import Toast from 'openvue/toast';
import { useToast } from 'openvue/usetoast';
import SampleClientSelector from './SampleClientSelector.vue';
import SampleInputFields from './SampleInputFields.vue';
import SampleProjectEditor from './SampleProjectEditor.vue';
import SampleResearchSelector from './SampleResearchSelector.vue';
import SelectedSampleResearch from './SelectedSampleResearch.vue';
import { useCreateSampleStore } from '../stores/createSampleStore';
import { useSampleResearchStore } from '../stores/sampleResearchStore';

const props = defineProps({
    endpoints: { type: Object, required: true },
});

const store = useCreateSampleStore();
const researchStore = useSampleResearchStore();
const toast = useToast();
const clientSelector = ref(null);
const sampleInputFields = ref(null);
const researchSelector = ref(null);

function editProfileAssay(entry, assay) {
    researchSelector.value?.editProfileAssay(entry, assay);
}

function editRoaming(entry) {
    researchSelector.value?.editRoaming(entry);
}

async function submit() {
    const sample = await store.submit();
    if (!sample) return;
    toast.add({ severity: 'success', summary: `Sample was saved as ${sample.barcode}`, life: 3500 });
    await nextTick();
    sampleInputFields.value?.focusDescription();
}

store.configure(props.endpoints);
onMounted(async () => {
    await store.initialize();
    await nextTick();
    clientSelector.value?.focus();
});
</script>

<template>
    <Toast position="top-right" />
    <form @submit.prevent="submit">
        <div v-if="store.error || store.errorMessages.length" class="notice error" role="alert"><strong>{{ store.error || 'Controleer de invoer.' }}</strong><ul v-if="store.errorMessages.length"><li v-for="message in store.errorMessages" :key="message">{{ message }}</li></ul></div>
        <div v-if="store.loading" class="sample-loading"><LoaderCircle class="spin" :size="22" aria-hidden="true" />Formulier laden</div>
        <div v-else class="sample-create-grid">
            <div class="sample-create-column">
                <section class="sample-panel"><h2><Building2 :size="16" aria-hidden="true" />Klant</h2><div class="sample-panel-body form-grid"><sample-client-selector ref="clientSelector" /></div></section>
                <section class="sample-panel"><h2><BriefcaseBusiness :size="16" aria-hidden="true" />Projectinformatie</h2><div class="sample-panel-body form-grid"><sample-project-editor /></div></section>
            </div>
            <div class="sample-create-column">
                <section class="sample-panel"><h2><Tag :size="16" aria-hidden="true" />Monsterinformatie</h2><div class="sample-panel-body form-grid"><sample-input-fields ref="sampleInputFields" /></div></section>
                <section class="sample-panel"><h2 class="sample-panel-lock-heading"><span><Paperclip :size="16" aria-hidden="true" />Geselecteerd onderzoek</span><button class="panel-lock-button" type="button" :title="researchStore.locked ? 'Onderzoek niet bewaren' : 'Onderzoek bewaren voor volgend monster'" @click="researchStore.toggleLock"><Lock v-if="researchStore.locked" :size="14" /><Unlock v-else :size="14" />{{ researchStore.locked ? 'Vast' : 'Los' }}</button></h2><div class="sample-panel-body"><selected-sample-research @edit-profile-assay="editProfileAssay" @edit-roaming="editRoaming" /></div></section>
            </div>
            <div class="sample-create-column">
                <section class="sample-panel"><h2><FlaskConical :size="16" aria-hidden="true" />Onderzoek</h2><div class="sample-panel-body"><sample-research-selector ref="researchSelector" /></div></section>
            </div>
        </div>
        <div class="form-actions sample-create-actions"><button class="button primary" type="submit" :disabled="store.loading || store.submitting"><LoaderCircle v-if="store.submitting" class="spin" :size="16" aria-hidden="true" /><Save v-else :size="16" aria-hidden="true" />Monster aanmelden</button></div>
    </form>
</template>