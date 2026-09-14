<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    options: { type: Array, default: () => [] },
    selected: { type: Array, default: () => [] },
    inputName: { type: String, default: 'meta_assays' },
    searchPlaceholder: { type: String, default: 'Zoek analyse' },
    emptyMessage: { type: String, default: 'Geen analyses gevonden.' },
    ariaLabel: { type: String, default: 'Beschikbare meta-analyses' },
    initialType: { type: [Number, String], default: 1 },
});

const isMeta = ref(String(props.initialType) === '4');
let typeSelect;

function syncVisibility() {
    isMeta.value = typeSelect?.value === '4';
}

onMounted(() => {
    typeSelect = document.getElementById('type');
    typeSelect?.addEventListener('change', syncVisibility);
    syncVisibility();
});

onBeforeUnmount(() => {
    typeSelect?.removeEventListener('change', syncVisibility);
});
</script>

<template>
    <fieldset v-show="isMeta" class="form-section">
        <legend>Meta-analyse</legend>
        <div class="field selector-wide">
            <option-selector
                :options="options"
                :selected="selected"
                :input-name="inputName"
                :search-placeholder="searchPlaceholder"
                :empty-message="emptyMessage"
                :aria-label="ariaLabel"
            />
        </div>
    </fieldset>
</template>