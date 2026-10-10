<script setup>
import { ref } from 'vue';
import { ArrowLeft, FileClock, FileText, Pencil, Printer, Star } from '@lucide/vue';
import { useProjectSearchStore } from '../stores/projectSearchStore';
import ShowRevisions from './ShowRevisions.vue';

const store = useProjectSearchStore();
const revisionsVisible = ref(false);

function displayDate(value) {
    if (!value) return '-';
    if (/^\d+$/.test(String(value))) return new Date(Number(value) * 1000).toLocaleDateString('nl-NL');
    return value;
}
</script>

<template>
    <section class="sample-panel project-detail-panel">
        <h2>
            Project
            <span class="lookup-tools">
                <button class="icon-button" title="Projectrevisies" aria-label="Projectrevisies" :disabled="!store.projectData || store.loadingProject" @click="revisionsVisible = true"><FileClock :size="15" /></button>
                <button class="icon-button" title="Rapportages (nog niet beschikbaar)" disabled><Printer :size="15" /></button>
                <button class="icon-button" title="Project volgen (nog niet beschikbaar)" disabled><Star :size="15" /></button>
                <button class="icon-button" title="Project wijzigen (nog niet beschikbaar)" disabled><Pencil :size="15" /></button>
            </span>
        </h2>
        <div v-if="store.loadingProject" class="sample-panel-body muted" role="status">Project laden...</div>
        <div v-else-if="store.projectData" class="project-detail-body">
            <dl class="project-detail-list">
                <div><dt>Referentie MAZ</dt><dd>{{ store.projectData.project.reference || '-' }}</dd></div>
                <div><dt>Referentie klant</dt><dd>{{ store.projectData.project.project_name || '-' }}</dd></div>
                <div><dt>Klant</dt><dd>{{ store.projectData.project.client_name || '-' }}</dd></div>
                <div><dt>Projectdatum</dt><dd>{{ displayDate(store.projectData.project.project_date) }}</dd></div>
                <div><dt>Revisie</dt><dd>{{ store.projectData.project.revision }}</dd></div>
                <div v-for="field in store.projectData.fields" :key="field.name"><dt>{{ field.label }}</dt><dd>{{ field.value }}</dd></div>
            </dl>
            <div v-if="store.projectData.project.locked" class="project-lock-warning">Autorisatie geblokkeerd: {{ store.projectData.project.lock_message || 'Geen reden opgegeven.' }}</div>
            <div class="project-placeholder-row"><FileText :size="15" /><span>PDF, portal en rapportfuncties volgen in een latere fase.</span></div>
            <div class="project-detail-actions"><button class="button primary" type="button" @click="store.backToResults()"><ArrowLeft :size="15" />Terug naar zoeken</button></div>
        </div>
        <p v-else class="empty-state">Selecteer een gevonden project.</p>
        <ShowRevisions v-model:visible="revisionsVisible" :endpoint="store.endpoints.revisions" scope="project" :scope-id="store.selectedProjectId" title="Projectrevisies" />
    </section>
</template>