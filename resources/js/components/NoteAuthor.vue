<script setup>
import { computed } from 'vue';
import Avatar from 'openvue/avatar';
import { noteDate } from '../notes.js';

const props = defineProps({
    author: { type: Object, default: null },
    date: { type: String, default: '' },
});
const name = computed(() => props.author?.name ?? 'Systeem');
const image = computed(() => props.author?.avatar_url ?? undefined);
</script>

<template>
    <span class="flex min-w-0 items-center gap-1.5 text-xs">
        <Avatar :image="image" :label="image ? undefined : name.charAt(0).toUpperCase()" shape="circle" class="size-5 shrink-0 text-[10px]" aria-hidden="true" />
        <strong class="truncate">{{ name }}</strong>
        <time v-if="date" class="shrink-0 text-muted" :datetime="date">{{ noteDate(date) }}</time>
    </span>
</template>
