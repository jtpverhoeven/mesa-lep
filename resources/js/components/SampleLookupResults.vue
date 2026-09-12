<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { LoaderCircle, RefreshCw } from '@lucide/vue';
import { useSampleLookupStore } from '../stores/sampleLookupStore';

const store = useSampleLookupStore();
const resultEntry = ref(null);
const dilutionGroups = computed(() => {
    const groups = [];

    for (const row of store.resultData?.rows ?? []) {
        const group = groups.find((item) => item.df === row.df);
        if (group) group.rows.push(row);
        else groups.push({ df: row.df, rows: [row] });
    }

    return groups;
});

watch(() => store.selectedId, (analysisId) => store.loadResults(analysisId), { immediate: true });
watch(() => store.resultData, async (resultData) => {
    if (!resultData || store.scannedResultFollowNumber === null) return;

    await nextTick();
    const row = resultData.rows.find((item) => String(item.follow_no) === store.scannedResultFollowNumber);
    resultEntry.value?.querySelector(`[data-result-id="${row?.id}"] input:not(:disabled)`)?.focus();
    store.scannedResultFollowNumber = null;
});

function dilutionName(df) {
    if (Number(df) === 1) return 'Origineel monster';

    const dilutions = store.selected?.settings?.dillutions ?? {};
    const match = Object.entries(dilutions).find(([, value]) => Number(value) === Number(df));
    if (match && match[0] !== '0') return match[0];

    const decimals = String(df).split('.')[1]?.length;
    return decimals ? `-${decimals}` : String(df);
}

function replicateName(replicate) {
    if (Number(replicate) === 1) return 'Duplo';
    if (Number(replicate) === 2) return 'Triplo';
    return `Duplo nummer: ${replicate}`;
}

function saving(row, field) {
    return Boolean(store.resultSaving[`${row.id}:${field.name}`]);
}

function filterKey(event, row, field) {
    if (event.ctrlKey || event.metaKey || event.altKey || event.key.length !== 1) return;

    const filter = Number(field.filter);
    if (filter === 1 && event.key === '*') {
        event.preventDefault();
        row.data[field.name] = '>';
        store.saveResult(row, field.name);
        return;
    }

    const allowed = {
        1: '1234567890,<>',
        2: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
        3: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        4: '+-',
    }[filter];

    if (allowed && !allowed.includes(event.key)) event.preventDefault();
}
</script>

<template>
    <div ref="resultEntry" class="lookup-result-entry">
        <p v-if="!store.selected" class="empty-state">Selecteer een analyse om resultaten in te voeren.</p>
        <p v-else-if="store.resultsLoading" class="result-loading" role="status"><LoaderCircle class="spin" :size="17" />Resultaatvelden laden...</p>
        <div v-else-if="store.resultError && !store.resultData" class="result-load-error" role="alert">
            <p>{{ store.resultError }}</p>
            <button class="button" type="button" @click="store.loadResults(store.selectedId)"><RefreshCw :size="15" />Opnieuw laden</button>
        </div>
        <p v-else-if="!store.resultData?.fields.length" class="empty-state">Geen resultaatvelden voor deze analyse.</p>
        <p v-else-if="!dilutionGroups.length" class="empty-state">Geen resultaatregels voor deze analyse.</p>
        <template v-else>
            <p v-if="store.resultError" class="result-save-error" role="alert">{{ store.resultError }}</p>
            <section v-for="group in dilutionGroups" :key="group.df" class="result-dilution">
                <h4>Verdunning: {{ dilutionName(group.df) }}</h4>
                <div v-for="row in group.rows" :key="row.id" class="result-replicate" :data-result-id="row.id">
                    <h5 v-if="Number(row.rep) > 0">{{ replicateName(row.rep) }}</h5>
                    <label v-for="field in store.resultData.fields" :key="field.name" class="result-field">
                        <span>{{ field.label }}</span>
                        <span class="result-control">
                            <input
                                v-model="row.data[field.name]"
                                type="text"
                                :name="field.name"
                                :placeholder="field.label"
                                :inputmode="Number(field.filter) === 1 ? 'decimal' : 'text'"
                                :disabled="store.readOnly || saving(row, field)"
                                @keydown="filterKey($event, row, field)"
                                @keydown.enter.prevent="$event.currentTarget.blur()"
                                @change="store.saveResult(row, field.name)"
                            >
                            <LoaderCircle v-if="saving(row, field)" class="spin" :size="15" aria-label="Opslaan" />
                        </span>
                    </label>
                </div>
            </section>
        </template>
    </div>
</template>

<style scoped>
.lookup-result-entry { display:grid; gap:10px; }
.result-loading { display:flex; align-items:center; justify-content:center; gap:8px; min-height:100px; margin:0; color:var(--muted); }
.result-load-error { display:grid; justify-items:start; gap:10px; }
.result-load-error p { margin:0; color:#903e32; }
.result-save-error { margin:0; padding:8px 9px; border:1px solid #d9aba3; background:#fff1ed; color:#903e32; font-size:11px; }
.result-dilution { display:grid; gap:9px; }
.result-dilution + .result-dilution { margin-top:3px; padding-top:12px; border-top:1px solid var(--line); }
.result-dilution h4 { margin:0; font-size:11px; font-weight:600; color:var(--muted); }
.result-replicate { display:grid; gap:8px; }
.result-replicate + .result-replicate { padding-top:10px; border-top:1px solid var(--line); }
.result-replicate h5 { margin:0; font-size:10px; font-weight:700; text-transform:uppercase; color:var(--accent); }
.result-field { display:grid; grid-template-columns:minmax(90px,.8fr) minmax(0,1.4fr); align-items:center; gap:8px; font-size:11px; }
.result-field > span:first-child { overflow-wrap:anywhere; }
.result-control { position:relative; display:flex; align-items:center; min-width:0; }
.result-control input { width:100%; min-width:0; min-height:32px; padding:6px 30px 6px 8px; border:1px solid #9eabb2; border-radius:2px; background:var(--surface); color:var(--ink); font:inherit; }
.result-control svg { position:absolute; right:8px; color:var(--accent); pointer-events:none; }
@media (max-width:420px) { .result-field { grid-template-columns:minmax(0,1fr); gap:4px; } }
</style>