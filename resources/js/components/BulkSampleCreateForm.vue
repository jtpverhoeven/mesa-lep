<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { BriefcaseBusiness, Building2, LoaderCircle, Save, Tag } from '@lucide/vue';
import InputNumber from 'openvue/inputnumber';
import Toast from 'openvue/toast';
import { useToast } from 'openvue/usetoast';
import BulkSampleRows from './BulkSampleRows.vue';
import SampleClientSelector from './SampleClientSelector.vue';
import SampleProjectEditor from './SampleProjectEditor.vue';
import { useBulkSampleStore } from '../stores/bulkSampleStore';
import { useProjectSelectorStore } from '../stores/projectSelectorStore';

const props = defineProps({ registrationType: { type: String, required: true }, endpoints: { type: Object, required: true } });
const store = useBulkSampleStore();
const projects = useProjectSelectorStore();
const toast = useToast();
const clientSelector = ref(null);
const unavailable = computed(() => store.loading || store.submitting || projects.loadingProject || projects.loadingProjects);

store.configure(props.registrationType, props.endpoints);
watch(() => store.matrices, () => store.refreshBarcodes());
watch(() => projects.selectedId, () => store.renumberRows());

async function submit() {
    const result = await store.submit();
    if (!result) return;
    toast.add({ severity: 'success', summary: result.message, life: 4000 });
    await nextTick();
    clientSelector.value?.focus();
}

onMounted(async () => {
    await store.initialize();
    await nextTick();
    clientSelector.value?.focus();
});
</script>

<template>
    <Toast position="top-right" />
    <!-- <div class="page-heading"><h1>{{ store.legionella ? 'Legionella aanmelden' : 'RODAC aanmelden' }}</h1></div> -->
    <div v-if="store.error || store.errorMessages.length" class="notice error" role="alert"><strong>{{ store.error || 'Controleer de invoer.' }}</strong><ul v-if="store.errorMessages.length"><li v-for="message in store.errorMessages" :key="message">{{ message }}</li></ul></div>
    <div v-if="store.loading" class="sample-loading"><LoaderCircle class="spin" :size="22" />Formulier laden</div>
    <form v-else @submit.prevent="submit">
        <fieldset :disabled="store.submitting" class="bulk-registration-fields">
            <div class="bulk-sample-grid">
                <div class="sample-create-column">
                    <section class="sample-panel"><h2><Building2 :size="16" />Klant</h2><div class="sample-panel-body form-grid">
                        <SampleClientSelector ref="clientSelector" :registration-store="store" show-details />
                    </div></section>
                    <section class="sample-panel"><h2><Tag :size="16" />Monsterinformatie</h2><div class="sample-panel-body form-grid">
                        <div class="field wide"><label for="bulk-sample-count">Aantal monsters</label><InputNumber input-id="bulk-sample-count" :model-value="store.count" :min="0" :use-grouping="false" @input="store.resizeRows($event.value)" @blur="store.resizeRows($event.value)" @update:model-value="store.resizeRows($event)" /></div>
                        <div v-if="!store.legionella" class="field wide"><label for="bulk-sampling-method">Monstername</label><select id="bulk-sampling-method" v-model="store.form.sampling_method"><option v-for="method in store.samplingMethods" :key="method.id" :value="method.id">{{ method.name }}</option></select></div>
                    </div></section>
                    <section class="sample-panel"><h2><BriefcaseBusiness :size="16" />Projectinformatie</h2><div class="sample-panel-body form-grid">
                        <SampleProjectEditor />
                        <div v-if="!projects.selectedId" class="field wide"><label for="bulk-project-name">Projectnaam</label><input id="bulk-project-name" v-model="projects.form.project_name" type="text" maxlength="128"></div>
                    </div></section>
                </div>
                <div class="sample-create-column">
                    <section class="sample-panel"><h2><Tag :size="16" />{{ store.rows.length }} monsters</h2>
                        <div v-if="store.legionella && !store.profiles.length" class="notice error" role="alert">Geen Legionella-profielen geconfigureerd.</div>
                        <div v-if="!store.legionella && store.rows.length" class="sample-panel-body field"><label for="bulk-reference">Monster / referentienummer</label><input id="bulk-reference" :value="store.rows[0]?.barcode" disabled></div>
                        <BulkSampleRows :rows="store.rows" :legionella="store.legionella" :profiles="store.profiles" :sampling-methods="store.samplingMethods" :previewing="store.previewing" />
                        <p v-if="store.previewError" class="error-text sample-panel-body" role="alert">{{ store.previewError }}</p>
                    </section>
                    <section class="sample-panel"><h2>Notities</h2><div class="sample-panel-body field"><label class="sr-only" for="bulk-sample-note">Monsternotities</label><textarea id="bulk-sample-note" v-model="store.form.sample_note" rows="4"></textarea></div></section>
                    <div class="form-actions sample-create-actions"><button class="button primary" type="submit" :disabled="unavailable || !store.rows.length || (store.legionella && !store.profiles.length)"><LoaderCircle v-if="store.submitting" class="spin" :size="16" /><Save v-else :size="16" />Monsters aanmelden</button></div>
                </div>
            </div>
        </fieldset>
    </form>
</template>

<style scoped>
.bulk-registration-fields { min-width:0; margin:0; padding:0; border:0; }
.bulk-sample-grid { display:grid; grid-template-columns:minmax(250px, 1fr) minmax(0, 2fr); gap:14px; align-items:start; }
.field :deep([data-pc-name='inputnumber']) { width:100%; }
@media (max-width:1000px) { .bulk-sample-grid { grid-template-columns:minmax(0,1fr); } }
</style>