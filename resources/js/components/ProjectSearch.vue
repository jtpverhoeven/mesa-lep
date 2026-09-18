<script setup>
import { LoaderCircle } from '@lucide/vue';
import { useProjectSearchStore } from '../stores/projectSearchStore';
import ProjectDetailsPanel from './ProjectDetailsPanel.vue';
import ProjectSamplePanel from './ProjectSamplePanel.vue';
import ProjectSamplesPanel from './ProjectSamplesPanel.vue';
import ProjectSearchPanel from './ProjectSearchPanel.vue';

const props = defineProps({
    endpoints: { type: Object, required: true },
    permissions: { type: Object, required: true },
});
const store = useProjectSearchStore();
store.configure(props.endpoints);

function displayDate(value) {
    if (!value) return '-';
    if (/^\d+$/.test(String(value))) return new Date(Number(value) * 1000).toLocaleDateString('nl-NL');
    return value;
}
</script>

<template>
    <div class="project-search-page">
        <div v-if="store.error" class="notice error" role="alert">{{ store.error }}</div>
        <div class="project-search-grid" :class="{ 'project-search-grid-empty': !store.selectedProjectId }">
            <div class="project-search-column">
                <ProjectSearchPanel />
                <ProjectDetailsPanel v-if="store.selectedProjectId" />
            </div>
            <ProjectSamplesPanel v-if="store.selectedProjectId" />
            <ProjectSamplePanel v-if="store.selectedProjectId" :can-update="permissions.updateSample" />
        </div>
        <section v-if="store.showsResults" class="sample-panel project-results-panel">
            <h2>Gevonden projecten <LoaderCircle v-if="store.searching" class="spin" :size="15" /></h2>
            <div v-if="store.projects.length" class="table-scroll">
                <table class="data-table project-results-table">
                    <thead><tr><th>Status</th><th>Referentie MAZ</th><th>Revisie</th><th>Referentie klant</th><th>Aantal monsters</th><th>Bemonsterdatum</th><th>Ontvangstdatum</th><th>Inzetdatum</th></tr></thead>
                    <tbody>
                        <tr v-for="project in store.projects" :key="project.id">
                            <td><span class="project-status">{{ project.status }}</span></td>
                            <td><button class="text-button" type="button" @click="store.selectProject(project.id)">MAZ-L {{ project.reference || project.id }}</button></td>
                            <td>{{ project.revision }}</td>
                            <td>{{ project.project_name || '-' }}</td>
                            <td>{{ project.samples_count }}</td>
                            <td>{{ displayDate(project.sampling_date) }}</td>
                            <td>{{ displayDate(project.received_date) }}</td>
                            <td>{{ project.inoculation_date }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else-if="!store.searching" class="empty-state">Geen projecten gevonden.</p>
        </section>
    </div>
</template>

<style scoped>
.project-search-page { min-width:0; }
@media(max-width:700px) { .project-search-page { width:calc(100vw - 28px); max-width:calc(100vw - 28px); } }
</style>