<script setup>
import { computed, ref, watch } from 'vue';
import { FileClock, FileText, FlaskConical, FolderOpen, MessageSquareText, Search, StickyNote, Trash2 } from '@lucide/vue';
import Badge from 'openvue/badge';
import Tab from 'openvue/tab';
import TabList from 'openvue/tablist';
import TabPanel from 'openvue/tabpanel';
import TabPanels from 'openvue/tabpanels';
import Tabs from 'openvue/tabs';
import { useProjectSearchStore } from '../stores/projectSearchStore';
import { projectReasons } from '../stores/keyringStore';
import { useNoteStore } from '../stores/noteStore';
import AppConfirmDialog from './AppConfirmDialog.vue';
import ProjectSampleFields from './ProjectSampleFields.vue';
import ProjectSampleResults from './ProjectSampleResults.vue';
import ReportNoteEditor from './ReportNoteEditor.vue';
import SampleNoteFeed from './SampleNoteFeed.vue';
import SampleFiles from './SampleFiles.vue';
import SampleMetadataEditor from './SampleMetadataEditor.vue';
import ShowRevisions from './ShowRevisions.vue';

defineProps({
    canUpdate: { type: Boolean, required: true },
    canManageFiles: { type: Boolean, required: true },
    canDelete: { type: Boolean, default: false },
    canVeto: { type: Boolean, default: false },
});

const store = useProjectSearchStore();
const noteStore = useNoteStore();
const deleteDialogVisible = ref(false);
const readOnlyMessage = computed(() => (projectReasons(store.projectData?.reasons).length
    ? 'Dit project is vergrendeld of geautoriseerd.'
    : 'Dit monster is in gebruik door een andere gebruiker.'));
const revisionsVisible = ref(false);

watch(() => [store.selectedProjectId, store.selectedSampleId, store.projectReadOnly], () => {
    deleteDialogVisible.value = false;
});

function reportNoteSaved(note) {
    store.sampleData.report_note = note;
    if (store.selectedSample) store.selectedSample.has_report_note = note !== null;
}

function internalNotesChanged(notes) {
    store.sampleData.notes = notes;
    if (store.selectedSample) store.selectedSample.has_internal_note = notes.length > 0;
}

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
                <button v-if="canDelete && !store.projectReadOnly" class="icon-button" type="button" title="Monster verwijderen" aria-label="Monster verwijderen" :disabled="!store.sampleData || store.deletingSample || store.loadingSample" @click="deleteDialogVisible = true"><Trash2 :size="15" /></button>
                <button class="icon-button" type="button" title="Monsterrevisies" aria-label="Monsterrevisies" :disabled="!store.sampleData || store.loadingSample" @click="revisionsVisible = true"><FileClock :size="15" /></button>
            </span>
        </h2>
        <p v-if="store.sampleError" class="project-sample-error" role="alert">{{ store.sampleError }}</p>
        <p v-if="store.loadingSample" class="empty-state" role="status">Monstergegevens en resultaten laden...</p>
        <template v-else-if="store.sampleData">        
            <Tabs value="details" class="panel-tabs">
                <TabPanels>
                    <TabPanel value="details"><ProjectSampleFields :sample-data="store.sampleData" :can-update="canUpdate" :read-only="store.sampleReadOnly" :read-only-message="readOnlyMessage" :saving-fields="store.savingSample" @save="store.updateSampleField" /></TabPanel>
                    <TabPanel value="metadata" class="project-sample-metadata-panel"><SampleMetadataEditor v-model:metadata="store.sampleData.metadata" :sample-id="store.sampleData.sample.id" :endpoint="store.endpoints.metadata" :read-only="!canUpdate || store.sampleReadOnly" /></TabPanel>
                    <TabPanel value="files">
                        <SampleFiles
                            :key="store.sampleData.sample.id"
                            v-model:files="store.sampleData.sample_files"
                            :sample-id="store.sampleData.sample.id"
                            :endpoints="{ store: store.endpoints.sampleFiles, visibility: store.endpoints.sampleFileVisibility, destroy: store.endpoints.sampleFileDelete }"
                            :can-manage="canManageFiles"
                            :locked="store.sampleFilesLocked"
                            :project-read-only="store.projectReadOnly"
                        />
                    </TabPanel>
                    <TabPanel value="report">
                        <ReportNoteEditor :key="store.sampleData.sample.id" :note="store.sampleData.report_note" :endpoint="noteStore.endpoint('sampleReportNote', { project: store.selectedProjectId, sample: store.sampleData.sample.id })" :read-only="store.sampleReadOnly" :lock-target="store.sampleData" @update:note="reportNoteSaved" />
                    </TabPanel>
                    <TabPanel value="notes">
                        <SampleNoteFeed :key="store.sampleData.sample.id" :notes="store.sampleData.notes" :sample-id="store.sampleData.sample.id" @update:notes="internalNotesChanged" />
                    </TabPanel>
                </TabPanels>
                <TabList>
                    <Tab value="details"><FlaskConical :size="14" />Details</Tab>
                    <Tab value="metadata"><FolderOpen :size="14" />Metadata</Tab>
                    <Tab value="files"><FileText :size="14" />Bestanden</Tab>
                    <Tab value="report" title="Notitie op het rapport"><MessageSquareText :size="14" />Rapport<Badge v-if="store.sampleData.report_note" value="!" severity="warn" size="small" aria-label="Rapportnotitie aanwezig" /></Tab>
                    <Tab value="notes" title="Interne notities"><StickyNote :size="14" />Intern<Badge v-if="store.sampleData.notes.length" :value="store.sampleData.notes.length" severity="warn" size="small" :aria-label="`${store.sampleData.notes.length} interne notities`" /></Tab>
                </TabList>
            </Tabs>
            <ProjectSampleResults :can-veto="canVeto" />
        </template>
        <p v-else class="empty-state">Selecteer een monster uit de projectinhoud.</p>
        <ShowRevisions v-model:visible="revisionsVisible" :endpoint="store.endpoints.revisions" scope="sample" :scope-id="store.selectedSampleId" :project-id="store.selectedProjectId" title="Monsterrevisies" />
        <AppConfirmDialog
            v-model:visible="deleteDialogVisible"
            title="Monster verwijderen"
            danger
            :message="`Monster ${store.sampleData?.sample.barcode ?? ''} verwijderen? Ook de bijbehorende analyses, resultaten en metadata worden verwijderd.`"
            confirm-label="Verwijderen"
            :loading="store.deletingSample"
            :disabled="!canDelete || store.projectReadOnly || !store.sampleData || store.loadingSample"
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
:deep(.project-sample-metadata-panel) { padding:6px; }
</style>