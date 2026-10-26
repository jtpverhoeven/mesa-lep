<script setup>
import { ref } from 'vue';
import { ArrowLeft, FileClock, Pencil, Printer, Star } from '@lucide/vue';
import { useProjectSearchStore } from '../stores/projectSearchStore';
import ProjectInfo from './ProjectInfo.vue';
import ShowProjectReports from './ShowProjectReports.vue';
import ShowRevisions from './ShowRevisions.vue';

const store = useProjectSearchStore();
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
            <ProjectInfo :info="store.projectData.project_info" mode="project" />
            <div v-if="store.projectData.project.locked" class="project-lock-warning">Autorisatie geblokkeerd: {{ store.projectData.project.lock_message || 'Geen reden opgegeven.' }}</div>
            
            <div class="project-detail-actions"><button class="button primary" type="button" @click="store.backToResults()"><ArrowLeft :size="15" />Terug naar zoeken</button></div>
        </div>
        <p v-else class="empty-state">Selecteer een gevonden project.</p>
        <ShowRevisions v-model:visible="revisionsVisible" :endpoint="store.endpoints.revisions" scope="project" :scope-id="store.selectedProjectId" title="Projectrevisies" />
        <ShowProjectReports v-model:visible="reportOverviewVisible" :endpoint="store.projectData?.report_overview_url ?? ''" />
    </section>
</template>