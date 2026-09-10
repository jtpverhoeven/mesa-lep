<script setup>
import { LoaderCircle } from '@lucide/vue';
import { useClientSelectorStore } from '../stores/clientSelectorStore';
import { useProjectSelectorStore } from '../stores/projectSelectorStore';

const clientStore = useClientSelectorStore();
const projectStore = useProjectSelectorStore();

function projectChanged(event) {
    projectStore.selectProject(event.target.value, clientStore.selected?.id);
}
</script>

<template>
    <div class="field wide">
        <label for="sample-project">Project</label>
        <div class="select-loading-wrap"><select id="sample-project" :value="projectStore.selectedId" :disabled="!clientStore.selected || projectStore.loadingProjects" @change="projectChanged"><option value="">Nieuw project</option><option v-for="project in projectStore.projects" :key="project.id" :value="String(project.id)">{{ project.project_name || `Project ${project.id}` }}</option></select><LoaderCircle v-if="projectStore.loadingProjects" class="spin" :size="16" aria-label="Projecten laden" /></div>
    </div>
    <p v-if="!clientStore.selected" class="panel-hint wide">Selecteer eerst een klant.</p>
    <template v-else>
        <div class="field wide">
            <label for="sample-project-name">Projectnaam</label>
            <input id="sample-project-name" v-model="projectStore.form.project_name" maxlength="128" :disabled="projectStore.loadingProject" required>
        </div>
        <div v-for="field in projectStore.fieldDefinitions" :key="field.name" class="field wide">
            <label :for="`project-field-${field.name}`">{{ field.alias }}</label>
            <textarea v-if="field.type === 'textarea'" :id="`project-field-${field.name}`" v-model="projectStore.form.custom_fields[field.name]" rows="3" :disabled="projectStore.loadingProject"></textarea>
            <input v-else :id="`project-field-${field.name}`" v-model="projectStore.form.custom_fields[field.name]" type="text" :placeholder="field.type === 'date' ? 'dd-mm-jjjj' : ''" :disabled="projectStore.loadingProject">
        </div>
        <p v-if="projectStore.error" class="error-text wide" role="alert">{{ projectStore.error }}</p>
    </template>
</template>