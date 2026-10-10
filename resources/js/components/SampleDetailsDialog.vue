<script setup>
import { computed } from 'vue';
import { LoaderCircle, X } from '@lucide/vue';
import AppDialog from './AppDialog.vue';
import ProjectSampleFields from './ProjectSampleFields.vue';

const props = defineProps({
    visible: Boolean,
    sampleData: { type: Object, default: null },
    barcode: { type: String, default: '' },
    canUpdate: { type: Boolean, required: true },
    readOnly: Boolean,
    loading: Boolean,
    savingFields: { type: Object, default: () => ({}) },
    error: { type: String, default: '' },
});
const emit = defineEmits(['update:visible', 'save']);
const busy = computed(() => props.loading || Object.keys(props.savingFields).length > 0);

function setVisible(visible) {
    if (!busy.value) emit('update:visible', visible);
}
</script>

<template>
    <AppDialog :visible="visible" :title="`Monsterdetails wijzigen · ${barcode}`" width="680px" :dismissable-mask="!busy" :close-on-escape="!busy" :closable="!busy" @update:visible="setVisible">
        <p v-if="error" class="error-text" role="alert">{{ error }}</p>
        <p v-if="loading" class="empty-state" role="status">Monstergegevens laden...</p>
        <ProjectSampleFields v-else-if="sampleData" :sample-data="sampleData" :can-update="canUpdate" :read-only="readOnly" :saving-fields="savingFields" @save="(source, field, value) => emit('save', source, field, value)" />
        <template #footer>
            <span v-if="Object.keys(savingFields).length" class="sample-details-saving" role="status"><LoaderCircle class="spin" :size="14" />Opslaan...</span>
            <button class="button" type="button" :disabled="busy" @click="setVisible(false)"><X :size="15" />Sluiten</button>
        </template>
    </AppDialog>
</template>

<style scoped>
.sample-details-saving { display:flex; align-items:center; gap:6px; margin-right:auto; color:var(--muted); font-size:12px; }
</style>