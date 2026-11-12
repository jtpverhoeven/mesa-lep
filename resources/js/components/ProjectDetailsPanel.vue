<script setup>
import { ref } from 'vue';
import { ArrowLeft, FileClock, Info, MessageSquareText, Pencil, Printer, Star } from '@lucide/vue';
import Badge from 'openvue/badge';
import Tab from 'openvue/tab';
import TabList from 'openvue/tablist';
import TabPanel from 'openvue/tabpanel';
import TabPanels from 'openvue/tabpanels';
import Tabs from 'openvue/tabs';
import { useProjectSearchStore } from '../stores/projectSearchStore';
import { useNoteStore } from '../stores/noteStore';
import ProjectInfo from './ProjectInfo.vue';
import ReportNoteEditor from './ReportNoteEditor.vue';
import ShowProjectReports from './ShowProjectReports.vue';
import ShowRevisions from './ShowRevisions.vue';

const store = useProjectSearchStore();
const noteStore = useNoteStore();
const revisionsVisible = ref(false);
const reportOverviewVisible = ref(false);

</script>

<template>
    <section class="sample-panel project-detail-panel">
        <h2>
            Project
            <span class="lookup-tools">
                <button class="icon-button" title="Projectrevisies" aria-label="Projectrevisies" :disabled="!store.projectData || store.loadingProject" @click="revisionsVisible = true"><FileClock :size="15" /></button>
                <button v-if="store.projectData?.report_overview_url" class="icon-button" type="button" title="Rapportages" aria-label="Rapportages" :disabled="store.loadingProject" @click="reportOverviewVisible = true"><Printer :size="15" /></button>
                <button class="icon-button" title="Project volgen (nog niet beschikbaar)" disabled><Star :size="15" /></button>
                <button class="icon-button" title="Project wijzigen (nog niet beschikbaar)" disabled><Pencil :size="15" /></button>
            </span>
        </h2>
        <div v-if="store.loadingProject" class="sample-panel-body muted" role="status">Project laden...</div>
        <div v-else-if="store.projectData" class="project-detail-body">
            <Tabs value="details" class="panel-tabs">
                <TabPanels>
                    <TabPanel value="details" class="flush"><ProjectInfo :info="store.projectData.project_info" mode="project" /></TabPanel>
                    <TabPanel value="note">
                        <ReportNoteEditor v-model:note="store.projectData.project_info.report_note" :key="store.selectedProjectId" :endpoint="noteStore.endpoint('projectReportNote', { project: store.selectedProjectId })" :read-only="store.projectReadOnly" :lock-target="store.projectData" />
                    </TabPanel>
                </TabPanels>
                <TabList>
                    <Tab value="details"><Info :size="14" />Details</Tab>
                    <Tab value="note" title="Notitie op het rapport"><MessageSquareText :size="14" />Project opmerkingen<Badge v-if="store.projectData.project_info.report_note" value="!" severity="warn" size="small" aria-label="Projectnotitie aanwezig" /></Tab>
                </TabList>
            </Tabs>
            <div class="project-detail-actions"><button class="button primary" type="button" @click="store.backToResults()"><ArrowLeft :size="15" />Terug naar zoeken</button></div>
        </div>
        <p v-else class="empty-state">Selecteer een gevonden project.</p>
        <ShowRevisions v-model:visible="revisionsVisible" :endpoint="store.endpoints.revisions" scope="project" :scope-id="store.selectedProjectId" title="Projectrevisies" />
        <ShowProjectReports v-model:visible="reportOverviewVisible" :endpoint="store.projectData?.report_overview_url ?? ''" />
    </section>
</template>