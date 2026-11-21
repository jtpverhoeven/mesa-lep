<script setup>
import AuditTable from './AuditTable.vue';

defineProps({ revisions: { type: Array, required: true }, emptyText: { type: String, default: 'Geen revisies.' } });

const columns = [
    { field: 'timestamp', header: 'Tijdstip' },
    { field: 'user', header: 'Gebruiker' },
    { field: 'event', header: 'Wijziging' },
    { field: 'from', header: 'Van' },
    { field: 'to', header: 'Naar' },
];
</script>

<template>
    <AuditTable :rows="revisions" :columns="columns" :empty-text="emptyText" />
</template>
