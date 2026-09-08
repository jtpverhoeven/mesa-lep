<script setup>
import { computed, ref } from 'vue';
import { Check, Search, X } from '@lucide/vue';

const props = defineProps({
    media: { type: Array, default: () => [] },
    selected: { type: Array, default: () => [] },
    inputName: { type: String, default: 'media' },
});

const search = ref('');
const selectedIds = ref(props.selected.map((id) => String(id)));

const normalizedMedia = computed(() => props.media.map((medium) => ({
    ...medium,
    id: String(medium.id),
    active: medium.active !== false,
}))); 

const selectedMedia = computed(() => selectedIds.value
    .map((id) => normalizedMedia.value.find((medium) => medium.id === id))
    .filter(Boolean));

const filteredMedia = computed(() => {
    const query = search.value.trim().toLocaleLowerCase();

    if (!query) return normalizedMedia.value;

    return normalizedMedia.value.filter((medium) => [medium.name, medium.short_name]
        .filter(Boolean)
        .some((value) => value.toLocaleLowerCase().includes(query)));
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
                <span class="sr-only">Media zoeken</span>
                <input v-model="search" type="search" placeholder="Zoek op naam of korte naam">
            </label>
            <span class="media-selector-count">{{ selectedIds.length }} geselecteerd</span>
        </div>

        <div v-if="selectedMedia.length" class="media-selected" aria-label="Geselecteerde media">
            <button v-for="medium in selectedMedia" :key="medium.id" class="media-chip" :class="{ inactive: !medium.active }" type="button" @click="toggle(medium.id)">
                <span>{{ medium.name }}</span>
                <X :size="13" aria-hidden="true" />
            </button>
        </div>

        <div class="media-options" role="listbox" aria-multiselectable="true" aria-label="Beschikbare media">
            <button
                v-for="medium in filteredMedia"
                :key="medium.id"
                class="media-option"
                :class="{ selected: isSelected(medium.id), inactive: !medium.active }"
                type="button"
                role="option"
                :aria-selected="isSelected(medium.id)"
                @click="toggle(medium.id)"
            >
                <span>
                    <strong>{{ medium.name }}</strong>
                    <small v-if="medium.short_name">{{ medium.short_name }}</small>
                    <small v-if="!medium.active">Inactief</small>
                </span>
                <Check v-if="isSelected(medium.id)" :size="16" aria-hidden="true" />
            </button>
            <p v-if="!filteredMedia.length" class="media-selector-empty">Geen media gevonden.</p>
        </div>

        <input v-for="id in selectedIds" :key="id" type="hidden" :name="`${inputName}[]`" :value="id">
    </div>
</template>