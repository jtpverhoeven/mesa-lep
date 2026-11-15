<script setup>
const props = defineProps({
    kind: { type: String, required: true },
    label: { type: String, required: true },
});

const titles = { requested: 'Losse analyse', profile: 'Profiel', added: 'Toegevoegd aan profiel', removed: 'Verwijderd uit profiel' };
</script>

<template>
    <span class="analysis-label" :class="`analysis-${props.kind}`" :title="titles[props.kind]"><span v-if="props.kind === 'added'" aria-hidden="true">+</span><span v-else-if="props.kind === 'removed'" aria-hidden="true">&minus;</span>{{ props.label }}</span>
</template>
