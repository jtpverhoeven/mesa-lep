<script setup>
import { computed } from 'vue';
import { vSyncValue } from '../syncValue.js';

const props = defineProps({
    value: { type: String, default: null },
    disabled: Boolean,
    label: { type: String, required: true },
    tone: { type: String, default: '' },
});
const emit = defineEmits(['commit']);

const isoValue = computed(() => {
    const match = /^(\d{2})-(\d{2})-(\d{4})$/.exec(props.value ?? '');

    return match ? `${match[3]}-${match[2]}-${match[1]}` : '';
});

function commitDate(event) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(event.target.value);

    emit('commit', match ? `${match[3]}-${match[2]}-${match[1]}` : '');
}
</script>

<template>
    <input v-sync-value="isoValue" class="confirmation-date" type="date" :disabled="disabled" :class="tone" :aria-label="label" @change="commitDate">
</template>

<style scoped>
.confirmation-date { width:100%; min-width:0; min-height:30px; border:1px solid var(--control-border); border-radius:2px; background:var(--surface); color:var(--ink); padding:3px 6px; font:inherit; }
.confirmation-date:disabled { background:var(--surface-alt); color:var(--muted); }
.confirmation-date.warning { border-color:#bf685d; background:#fff1ed; }
.confirmation-date.explained { border-color:#c78a2c; background:#fff2d9; }
</style>
