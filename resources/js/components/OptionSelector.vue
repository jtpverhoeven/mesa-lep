<script setup>
import { computed, ref } from 'vue';
import { Check, Search, X } from '@lucide/vue';

const props = defineProps({
    options: { type: Array, default: () => [] },
    selected: { type: Array, default: () => [] },
    inputName: { type: String, required: true },
    searchPlaceholder: { type: String, default: 'Zoeken' },
    emptyMessage: { type: String, default: 'Geen opties gevonden.' },
    ariaLabel: { type: String, default: 'Beschikbare opties' },
});

const search = ref('');
const selectedIds = ref(props.selected.map((id) => String(id)));

const normalizedOptions = computed(() => props.options.map((option) => ({
    ...option,
    id: String(option.id),
})));

const selectedOptions = computed(() => selectedIds.value
    .map((id) => normalizedOptions.value.find((option) => option.id === id))
    .filter(Boolean));

const filteredOptions = computed(() => {
    const query = search.value.trim().toLocaleLowerCase();

    if (!query) {
        return normalizedOptions.value;
    }

    return normalizedOptions.value.filter((option) => [option.name, option.detail]
        .filter(Boolean)
        .some((value) => String(value).toLocaleLowerCase().includes(query)));
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
    <div class="media-selector">
        <div class="media-selector-toolbar">
            <label class="media-search">
                <Search :size="15" aria-hidden="true" />
                <span class="sr-only">{{ searchPlaceholder }}</span>
                <input v-model="search" type="search" :placeholder="searchPlaceholder">
            </label>
            <span class="media-selector-count">{{ selectedIds.length }} geselecteerd</span>
        </div>

        <div v-if="selectedOptions.length" class="media-selected" :aria-label="`Geselecteerd: ${ariaLabel}`">
            <button v-for="option in selectedOptions" :key="option.id" class="media-chip" type="button" @click="toggle(option.id)">
                <span>{{ option.name }}</span>
                <X :size="13" aria-hidden="true" />
            </button>
        </div>

        <div class="media-options" role="listbox" aria-multiselectable="true" :aria-label="ariaLabel">
            <button
                v-for="option in filteredOptions"
                :key="option.id"
                class="media-option"
                :class="{ selected: isSelected(option.id) }"
                type="button"
                role="option"
                :aria-selected="isSelected(option.id)"
                @click="toggle(option.id)"
            >
                <span>
                    <strong>{{ option.name }}</strong>
                    <small v-if="option.detail">{{ option.detail }}</small>
                </span>
                <Check v-if="isSelected(option.id)" :size="16" aria-hidden="true" />
            </button>
            <p v-if="!filteredOptions.length" class="media-selector-empty">{{ emptyMessage }}</p>
        </div>

        <input v-for="id in selectedIds" :key="id" type="hidden" :name="`${inputName}[]`" :value="id">
    </div>
</template>