<script setup>
import { computed } from 'vue';
import Badge from 'openvue/badge';
import { useSidebarCountsStore } from '../stores/sidebarCountsStore';

const props = defineProps({
    counter: { type: String, required: true },
    severity: { type: String, default: 'info' },
    label: { type: String, default: '' },
    opensNotInoculated: Boolean,
});

const store = useSidebarCountsStore();
const count = computed(() => Number(store.counts[props.counter] ?? 0));
const title = computed(() => `${count.value} ${props.label}`.trim());
</script>

<template>
    <button v-if="count > 0 && opensNotInoculated" type="button" class="nav-badge-button" :title="title" :aria-label="title" @click="store.notInoculatedVisible = true">
        <Badge :value="count" :severity="severity" />
    </button>
    <Badge v-else-if="count > 0" :value="count" :severity="severity" :title="title" :aria-label="title" />
</template>
