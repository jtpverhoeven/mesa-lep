<script setup>
import { computed, ref } from 'vue';
import { Check, Search, X } from '@lucide/vue';

const props = defineProps({
    matrices: { type: Array, default: () => [] },
    selected: { type: Array, default: () => [] },
    inputName: { type: String, default: 'matrices' },
});

const search = ref('');
const selectedIds = ref(props.selected.map((id) => String(id)));

const normalizedMatrices = computed(() => props.matrices.map((matrix) => ({
    ...matrix,
    id: String(matrix.id),
})));

const selectedMatrices = computed(() => selectedIds.value
    .map((id) => normalizedMatrices.value.find((matrix) => matrix.id === id))
    .filter(Boolean));

const filteredMatrices = computed(() => {
    const query = search.value.trim().toLocaleLowerCase();

    if (!query) return normalizedMatrices.value;

    return normalizedMatrices.value.filter((matrix) => matrix.name
        .toLocaleLowerCase()
        .includes(query));
});

function isSelected(id) {
    return selectedIds.value.includes(id);
}

function toggle(id) {
    selectedIds.value = isSelected(id)
        ? selectedIds.value.filter((selectedId) => selectedId !== id)
        : [...selectedIds.value, id];
}
</script>

<template>
    <div class="matrix-selector">
        <div class="matrix-selector-toolbar">
            <label class="matrix-search">
                <Search :size="15" aria-hidden="true" />
                <span class="sr-only">Matrices zoeken</span>
                <input v-model="search" type="search" placeholder="Zoek op naam">
            </label>
            <span class="matrix-selector-count">{{ selectedIds.length }} geselecteerd</span>
        </div>

        <div v-if="selectedMatrices.length" class="matrix-selected" aria-label="Geselecteerde matrices">
            <button v-for="matrix in selectedMatrices" :key="matrix.id" class="matrix-chip" type="button" @click="toggle(matrix.id)">
                <span>{{ matrix.name }}</span>
                <X :size="13" aria-hidden="true" />
            </button>
        </div>

        <div class="matrix-options" role="listbox" aria-multiselectable="true" aria-label="Beschikbare matrices">
            <button
                v-for="matrix in filteredMatrices"
                :key="matrix.id"
                class="matrix-option"
                :class="{ selected: isSelected(matrix.id) }"
                type="button"
                role="option"
                :aria-selected="isSelected(matrix.id)"
                @click="toggle(matrix.id)"
            >
                <strong>{{ matrix.name }}</strong>
                <Check v-if="isSelected(matrix.id)" :size="16" aria-hidden="true" />
            </button>
            <p v-if="!filteredMatrices.length" class="matrix-selector-empty">Geen matrices gevonden.</p>
        </div>

        <input v-for="id in selectedIds" :key="id" type="hidden" :name="`${inputName}[]`" :value="id">
    </div>
</template>