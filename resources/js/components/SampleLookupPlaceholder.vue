<script setup>
import { useSampleLookupStore } from '../stores/sampleLookupStore';
defineProps({ feature: { type: String, required: true }, details: { default: null } });
const store = useSampleLookupStore();
</script>

<template>
    <div class="lookup-placeholder">
        <p>{{ feature }}: nog niet beschikbaar.</p>
        <pre>{{ JSON.stringify({ implemented: false, sample_id: store.data?.sample.id ?? null, sampleanalysis_id: store.selectedId, assay_base: store.selected?.assay_base ?? null, ...details }, null, 2) }}</pre>
    </div>
</template>