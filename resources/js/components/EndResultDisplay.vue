<script setup>
import { computed } from 'vue';
import { AlertCircle, CheckCircle2, LoaderCircle } from '@lucide/vue';
import DOMPurify from 'dompurify';

const props = defineProps({
    calculation: { type: Object, default: null },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
    emptyText: { type: String, default: 'Geen eindresultaat beschikbaar.' },
});

function display(value) {
    const normalized = value !== null && typeof value === 'object' ? JSON.stringify(value) : String(value ?? '-');
    return DOMPurify.sanitize(normalized, { ALLOWED_TAGS: ['sup'], ALLOWED_ATTR: [] });
}

const hidden = computed(() => {
    const value = props.calculation?.resultHide;
    if (Array.isArray(value)) return new Set(value);
    if (value && typeof value === 'object') return new Set(Object.keys(value).filter((key) => value[key]));
    return new Set();
});

const results = computed(() => Object.entries(props.calculation?.output ?? {})
    .filter(([key]) => !hidden.value.has(key))
    .map(([key, value]) => ({
        key,
        label: props.calculation?.resultMask?.[key] || key,
        value: display(value ?? props.calculation?.outputEn?.[key]),
        disposition: props.calculation?.disposition?.[key] ?? null,
    })));

const messages = computed(() => {
    const value = props.calculation?.messageBag;
    const items = Array.isArray(value) ? value : (value ? Object.values(value) : []);
    return items.filter(Boolean).map((message) => message !== null && typeof message === 'object' ? JSON.stringify(message) : String(message));
});
</script>

<template>
    <div class="end-result" :aria-busy="loading" aria-live="polite">
        <div v-if="loading" class="end-result-state" role="status">
            <LoaderCircle class="spin" :size="17" />Eindresultaat berekenen...
        </div>
        <div v-else-if="error" class="end-result-state end-result-error" role="alert">
            <AlertCircle :size="17" />
            <span>{{ error }}</span>
        </div>
        <div v-else-if="results.length" class="end-result-values">
            <div v-for="result in results" :key="result.key" class="end-result-row">
                <span class="end-result-label">{{ result.label }}</span>
                <strong v-html="result.value"></strong>
                <span v-if="result.disposition" class="end-result-disposition" :title="`Dispositie: ${result.disposition}`">{{ result.disposition }}</span>
            </div>
            <div class="end-result-status" :class="{ ready: calculation?.isReady }">
                <CheckCircle2 v-if="calculation?.isReady" :size="15" />
                <AlertCircle v-else :size="15" />
                {{ calculation?.isReady ? 'Gereed' : 'Nog niet gereed' }}
            </div>
            <ul v-if="messages.length" class="end-result-messages">
                <li v-for="(message, index) in messages" :key="index">{{ message }}</li>
            </ul>
        </div>
        <p v-else class="empty-state">{{ emptyText }}</p>
    </div>
</template>

<style scoped>
.end-result { min-width:0; }
.end-result-state { display:flex; align-items:center; gap:8px; min-height:52px; color:var(--muted); }
.end-result-error { color:#903e32; }
.end-result-error span { min-width:0; flex:1; overflow-wrap:anywhere; }
.end-result-error .icon-button { flex:none; margin:0; }
.end-result-values { display:grid; gap:10px; }
.end-result-row { display:grid; grid-template-columns:minmax(75px,.7fr) minmax(0,1.4fr) auto; align-items:center; gap:9px; min-height:40px; padding-bottom:9px; border-bottom:1px solid var(--line); }
.end-result-label { color:var(--muted); overflow-wrap:anywhere; }
.end-result-row strong { font-size:15px; overflow-wrap:anywhere; }
.end-result-disposition { display:grid; place-items:center; min-width:24px; height:24px; border:1px solid #9eabb2; background:var(--surface-alt); color:var(--ink); font-weight:700; }
.end-result-status { display:flex; align-items:center; gap:6px; color:#9a5500; font-size:11px; font-weight:700; }
.end-result-status.ready { color:#247448; }
.end-result-messages { display:grid; gap:5px; margin:0; padding-left:18px; color:var(--muted); font-size:11px; }
@media (max-width:420px) { .end-result-row { grid-template-columns:minmax(0,1fr) auto; } .end-result-label { grid-column:1/-1; } }
</style>