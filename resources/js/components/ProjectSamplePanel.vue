<script setup>
import { FileText, FlaskConical, FolderOpen } from '@lucide/vue';
import Tab from 'openvue/tab';
import TabList from 'openvue/tablist';
import TabPanel from 'openvue/tabpanel';
import TabPanels from 'openvue/tabpanels';
import Tabs from 'openvue/tabs';
import { useProjectSearchStore } from '../stores/projectSearchStore';
import ProjectSampleFields from './ProjectSampleFields.vue';
import ProjectSampleResults from './ProjectSampleResults.vue';
import SampleMetadataEditor from './SampleMetadataEditor.vue';

defineProps({ canUpdate: { type: Boolean, required: true } });

const store = useProjectSearchStore();
</script>

<template>
    <section class="sample-panel project-sample-panel" :aria-busy="store.loadingSample">
        <h2><FlaskConical :size="16" />Monsters en resultaten</h2>
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
    </section>
</template>

<style scoped>
.project-sample-sublead { margin:0; padding:9px 12px 5px; color:var(--muted); font-size:12px; }
.project-sample-error { margin:0; padding:9px 12px; border-bottom:1px solid #d9aba3; background:#fff1ed; color:#903e32; font-size:11px; }
:deep([data-pc-name='tablist']) { display:block; width:100%; border-top:1px solid var(--line); }
:deep([data-pc-name='tablist'] [data-pc-section='content']) { width:100%; overflow:hidden; }
:deep([data-pc-name='tablist'] [data-pc-section='tablist']) { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); width:100%; }
:deep([data-pc-name='tab']) { display:flex; align-items:center; justify-content:center; gap:5px; min-height:31px; padding:4px 6px; border:0; border-top:2px solid transparent; background:var(--surface-alt); color:var(--muted); font:inherit; font-size:11px; cursor:pointer; }
:deep([data-pc-name='tab'][data-p-active='true']) { border-top-color:var(--accent); background:var(--accent-faint); color:var(--accent); font-weight:700; }
:deep([data-pc-name='tabpanel']) { padding:12px; }
:deep(.project-sample-metadata-panel) { padding:6px; }
:deep([data-pc-section='prevbutton']),:deep([data-pc-section='nextbutton']) { display:none; }
</style>