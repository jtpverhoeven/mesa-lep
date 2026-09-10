<script setup>
import { LoaderCircle, Lock, Unlock } from '@lucide/vue';
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
        <div v-for="field in projectStore.fieldDefinitions" :key="field.name" class="field wide">
            <label class="lockable-field-label" :for="`project-field-${field.name}`"><span>{{ field.alias }}</span><button class="field-lock-button" type="button" :title="projectStore.lockedFields.includes(field.name) ? 'Waarde niet bewaren' : 'Waarde bewaren voor volgend project'" @click="projectStore.toggleFieldLock(field.name)"><Lock v-if="projectStore.lockedFields.includes(field.name)" :size="13" /><Unlock v-else :size="13" /></button></label>
            <textarea v-if="field.type === 'textarea'" :id="`project-field-${field.name}`" v-model="projectStore.form.custom_fields[field.name]" rows="3" :disabled="projectStore.loadingProject"></textarea>
            <input v-else :id="`project-field-${field.name}`" v-model="projectStore.form.custom_fields[field.name]" type="text" :placeholder="field.type === 'date' ? 'dd-mm-jjjj' : ''" :disabled="projectStore.loadingProject">
        </div>
        <p v-if="projectStore.error" class="error-text wide" role="alert">{{ projectStore.error }}</p>
    </template>
</template>