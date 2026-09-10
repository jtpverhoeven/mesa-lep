<script setup>
import { onMounted } from 'vue';
import { BriefcaseBusiness, Building2, FlaskConical, LoaderCircle, Paperclip, Save, Tag } from '@lucide/vue';
import SampleAssayPlaceholder from './SampleAssayPlaceholder.vue';
import SampleClientSelector from './SampleClientSelector.vue';
import SampleInputFields from './SampleInputFields.vue';
import SampleProjectEditor from './SampleProjectEditor.vue';
import { useCreateSampleStore } from '../stores/createSampleStore';

const props = defineProps({
    endpoints: { type: Object, required: true },
});

const store = useCreateSampleStore();
store.configure(props.endpoints);
onMounted(() => store.initialize());
</script>

<template>
    <form @submit.prevent="store.submit">
        <div v-if="store.success" class="notice success" role="status">{{ store.success }}</div>
        <div v-if="store.error || store.errorMessages.length" class="notice error" role="alert"><strong>{{ store.error || 'Controleer de invoer.' }}</strong><ul v-if="store.errorMessages.length"><li v-for="message in store.errorMessages" :key="message">{{ message }}</li></ul></div>
        <div v-if="store.loading" class="sample-loading"><LoaderCircle class="spin" :size="22" aria-hidden="true" />Formulier laden</div>
        <div v-else class="sample-create-grid">
            <div class="sample-create-column">
                <section class="sample-panel"><h2><Building2 :size="16" aria-hidden="true" />Klant</h2><div class="sample-panel-body form-grid"><sample-client-selector /></div></section>
                <section class="sample-panel"><h2><BriefcaseBusiness :size="16" aria-hidden="true" />Projectinformatie</h2><div class="sample-panel-body form-grid"><sample-project-editor /></div></section>
            </div>
            <div class="sample-create-column">
                <section class="sample-panel"><h2><Tag :size="16" aria-hidden="true" />Monsterinformatie</h2><div class="sample-panel-body form-grid"><sample-input-fields /></div></section>
                <section class="sample-panel"><h2><Paperclip :size="16" aria-hidden="true" />Geselecteerd onderzoek</h2><div class="sample-panel-body selected-research-empty">Geen onderzoek geselecteerd.</div></section>
            </div>
            <div class="sample-create-column">
                <section class="sample-panel"><h2><FlaskConical :size="16" aria-hidden="true" />Onderzoek</h2><div class="sample-panel-body assay-placeholder"><sample-assay-placeholder /></div></section>
            </div>
        </div>
        <div class="form-actions sample-create-actions"><button class="button primary" type="submit" :disabled="store.loading || store.submitting"><LoaderCircle v-if="store.submitting" class="spin" :size="16" aria-hidden="true" /><Save v-else :size="16" aria-hidden="true" />Monster aanmelden</button></div>
    </form>
</template>