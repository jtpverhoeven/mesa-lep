<script setup>
import { computed } from 'vue';

const props = defineProps({
    assays: { type: Array, default: () => [] },
    selectedAssayIds: { type: Array, default: () => [] },
    pendingAssayIds: { type: Array, default: () => [] },
    inputName: { type: String, default: '' },
});
const emit = defineEmits(['toggle']);
const selected = computed(() => new Set(props.selectedAssayIds.map(Number)));
const pending = computed(() => new Set(props.pendingAssayIds.map(Number)));
</script>

<template>
    <div v-if="assays.length" class="assay-association-grid">
        <label
            v-for="assay in assays"
            :key="assay.id"
            class="assay-association-option"
            :class="{ selected: selected.has(Number(assay.id)), inactive: !Number(assay.active), unavailable: assay.assigned_elsewhere }"
            :aria-busy="pending.has(Number(assay.id))"
        >
            <input
                type="checkbox"
                :name="inputName || undefined"
                :value="assay.id"
                :checked="selected.has(Number(assay.id))"
                :disabled="assay.assigned_elsewhere || pending.has(Number(assay.id))"
                @change="emit('toggle', Number(assay.id), $event.target.checked)"
            >
            <span class="assay-association-name">{{ assay.name }}</span>
            <span class="assay-association-id" title="Analyse-ID">{{ assay.id }}</span>
            <small v-if="assay.assigned_elsewhere">Gekoppeld aan andere portaalanalyse</small>
            <small v-else-if="pending.has(Number(assay.id))">Bijwerken...</small>
            <small v-else-if="!Number(assay.active)">Inactief</small>
        </label>
    </div>
    <p v-else class="empty-state">Geen analyses gevonden voor deze filters.</p>
</template>
