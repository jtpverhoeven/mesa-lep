<script setup>
import { ref, watch } from 'vue';
import { FileClock, FileText, FlaskConical, FolderOpen, Search, Trash2 } from '@lucide/vue';
import Tab from 'openvue/tab';
import TabList from 'openvue/tablist';
import TabPanel from 'openvue/tabpanel';
import TabPanels from 'openvue/tabpanels';
import Tabs from 'openvue/tabs';
import { useProjectSearchStore } from '../stores/projectSearchStore';
import AppConfirmDialog from './AppConfirmDialog.vue';
import ProjectSampleFields from './ProjectSampleFields.vue';
import ProjectSampleResults from './ProjectSampleResults.vue';
import SampleMetadataEditor from './SampleMetadataEditor.vue';
import ShowRevisions from './ShowRevisions.vue';

defineProps({ canUpdate: { type: Boolean, required: true }, canDelete: { type: Boolean, default: false } });

const store = useProjectSearchStore();
const deleteDialogVisible = ref(false);
const revisionsVisible = ref(false);

watch(() => [store.selectedProjectId, store.selectedSampleId, store.sampleReadOnly], () => {
    deleteDialogVisible.value = false;
});

async function confirmDelete() {
    if (await store.deleteSample()) deleteDialogVisible.value = false;
}
</script>

<template>
    <section class="sample-panel project-sample-panel" :aria-busy="store.loadingSample">
        <h2>
            <FlaskConical :size="16" />Monsters en resultaten
            <span class="lookup-tools">
                <a v-if="store.sampleData" class="icon-button" :href="store.endpoints.sampleLookup.replace('__BARCODE__', encodeURIComponent(store.sampleData.sample.barcode))" title="Ga naar monster" aria-label="Ga naar monster"><Search :size="15" /></a>
                <button v-else class="icon-button" title="Ga naar monster" aria-label="Ga naar monster" disabled><Search :size="15" /></button>
                <button v-if="canDelete && !store.sampleReadOnly" class="icon-button" type="button" title="Monster verwijderen" aria-label="Monster verwijderen" :disabled="!store.sampleData || store.deletingSample || store.loadingSample" @click="deleteDialogVisible = true"><Trash2 :size="15" /></button>
                <button class="icon-button" type="button" title="Monsterrevisies" aria-label="Monsterrevisies" :disabled="!store.sampleData || store.loadingSample" @click="revisionsVisible = true"><FileClock :size="15" /></button>
            </span>
        </h2>
        <p v-if="store.sampleError" class="project-sample-error" role="alert">{{ store.sampleError }}</p>
        <p v-if="store.loadingSample" class="empty-state" role="status">Monstergegevens en resultaten laden...</p>
        <template v-else-if="store.sampleData">        
            <Tabs value="details">
                <TabPanels>
                    <TabPanel value="details"><ProjectSampleFields :can-update="canUpdate" /></TabPanel>
                    <TabPanel value="metadata" class="project-sample-metadata-panel"><SampleMetadataEditor v-model:metadata="store.sampleData.metadata" :sample-id="store.sampleData.sample.id" :endpoint="store.endpoints.metadata" :read-only="!canUpdate || store.sampleReadOnly" /></TabPanel>
                    <TabPanel value="files"><p class="empty-state">Bestanden worden in een latere fase toegevoegd.</p></TabPanel>
                </TabPanels>
                <TabList>
                    <Tab value="details"><FlaskConical :size="14" />Details</Tab>
                    <Tab value="metadata"><FolderOpen :size="14" />Metadata</Tab>
                    <Tab value="files"><FileText :size="14" />Bestanden</Tab>
                </TabList>
            </Tabs>
            <ProjectSampleResults />
        </template>
        <p v-else class="empty-state">Selecteer een monster uit de projectinhoud.</p>
        <ShowRevisions v-model:visible="revisionsVisible" :endpoint="store.endpoints.revisions" scope="sample" :scope-id="store.selectedSampleId" :project-id="store.selectedProjectId" title="Monsterrevisies" />
        <AppConfirmDialog
            v-model:visible="deleteDialogVisible"
            title="Monster verwijderen"
            :message="`Monster ${store.sampleData?.sample.barcode ?? ''} verwijderen? Ook de bijbehorende analyses, resultaten en metadata worden verwijderd.`"
            confirm-label="Verwijderen"
            :loading="store.deletingSample"
            :disabled="!canDelete || store.sampleReadOnly || !store.sampleData || store.loadingSample"
            :error="store.sampleError"
            @confirm="confirmDelete"
        >
            <template #confirm-icon><Trash2 :size="15" /></template>
        </AppConfirmDialog>
    </section>
</template>

<style scoped>
.project-sample-sublead { margin:0; padding:9px 12px 5px; color:var(--muted); font-size:12px; }
.project-sample-error { margin:0; padding:9px 12px; border-bottom:1px solid #d9aba3; background:#fff1ed; color:#903e32; font-size:11px; }
:deep([data-pc-name='tablist']) { display:block; width:100%; border-top:1px solid var(--line); }
:deep([data-pc-name='tablist'] [data-pc-section='content']) { width:100%; overflow:hidden; }
:deep([data-pc-name='tablist'] [data-pc-section='tablist']) { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); width:100%; }
:deep([data-pc-name='tab']) { display:flex; align-items:center; justify-content:center; gap:5px; padding:9px 6px; border:0; background:var(--surface-alt); color:var(--ink); font:inherit; font-size:11px; cursor:pointer; }
:deep([data-pc-name='tab'][data-p-active='true']) { box-shadow:inset 0 -2px var(--accent); background:var(--accent-faint); color:var(--accent); }
:deep([data-pc-name='tabpanel']) { padding:12px; }
:deep(.project-sample-metadata-panel) { padding:6px; }
:deep([data-pc-section='prevbutton']),:deep([data-pc-section='nextbutton']) { display:none; }
</style>