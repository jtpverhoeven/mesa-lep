<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { ClipboardCheck, LoaderCircle, RefreshCw } from '@lucide/vue';
import { useSampleLookupStore } from '../stores/sampleLookupStore';
import { useConfirmationStore } from '../stores/confirmationStore';

const store = useSampleLookupStore();
const confirmationStore = useConfirmationStore();
const emit = defineEmits(['finished-entry']);
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

watch([() => store.resultData, () => store.plateFocusRevision], async ([resultData]) => {
    if (!resultData) return;

    await nextTick();
    const row = resultData.rows.find((item) => String(item.follow_no) === store.scannedPlateFollowNumber);
    const input = row
        ? resultEntry.value?.querySelector(`[data-result-id="${row.id}"] input:not(:disabled)`)
        : resultEntry.value?.querySelector('input:not(:disabled)');
    input?.focus();
    store.scannedPlateFollowNumber = null;
});

function finishEntry(event) {
    if (event.shiftKey) return;

    const inputs = [...(resultEntry.value?.querySelectorAll('input:not(:disabled)') ?? [])];
    if (event.currentTarget !== inputs.at(-1)) return;

    event.preventDefault();
    emit('finished-entry');
}

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

function confirmationApplicable(row) {
    const confirmation = confirmationStore.data;

    if (confirmation?.config?.mode !== 'per_plate') return false;
    if (Number(confirmation?.decision) !== 1) return false;

    const count = row.data?.kve;
    if (String(count).trim() === '>' || (String(count).trim() !== '' && Number(count) === 0)) return false;

    return confirmation.scopes?.some((scope) => String(scope.df) === String(row.df) && Number(scope.rep) === Number(row.rep) && scope.applicable);
}

function openConfirmation(row) {
    confirmationStore.open(store.selectedId, row.df, Number(row.rep));
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
                    <div v-if="Number(row.rep) > 0" class="result-replicate-heading"><h5>{{ replicateName(row.rep) }}</h5></div>
                    <label v-for="field in store.resultData.fields" :key="field.name" class="result-field">
                        <span>{{ field.label }}</span>
                        <span class="result-control" :class="{ 'has-confirmation': field.name === 'kve' && confirmationApplicable(row) }">
                            <input
                                v-model="row.data[field.name]"
                                type="text"
                                :name="field.name"
                                :placeholder="field.label"
                                :inputmode="Number(field.filter) === 1 ? 'decimal' : 'text'"
                                :disabled="store.readOnly || saving(row, field)"
                                @keydown="filterKey($event, row, field)"
                                @keydown.tab="finishEntry"
                                @keydown.enter.prevent="$event.currentTarget.blur()"
                                @change="store.saveResult(row, field.name)"
                            >
                            <LoaderCircle v-if="saving(row, field)" class="spin result-saving-indicator" :size="15" aria-label="Opslaan" />
                            <button v-if="field.name === 'kve' && confirmationApplicable(row)" class="result-confirmation-button" type="button" title="Bevestiging openen" :aria-label="`Bevestiging openen voor ${row.df}, replica ${row.rep}`" @click.prevent="openConfirmation(row)"><ClipboardCheck :size="15" /></button>
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
.result-replicate-heading { display:flex; align-items:center; justify-content:space-between; min-height:28px; }
.result-field { display:grid; grid-template-columns:minmax(90px,.8fr) minmax(0,1.4fr); align-items:center; gap:8px; font-size:11px; }
.result-field > span:first-child { overflow-wrap:anywhere; }
.result-control { position:relative; display:flex; align-items:center; min-width:0; }
.result-control input { width:100%; min-width:0; min-height:32px; padding:6px 30px 6px 8px; border:1px solid #9eabb2; border-radius:2px; background:var(--surface); color:var(--ink); font:inherit; }
.result-control.has-confirmation input { border-radius:2px 0 0 2px; }
.result-saving-indicator { position:absolute; right:8px; color:var(--accent); pointer-events:none; }
.result-control.has-confirmation .result-saving-indicator { right:40px; }
.result-confirmation-button { display:grid; flex:0 0 34px; align-self:stretch; place-items:center; min-height:32px; padding:0; border:1px solid #9eabb2; border-left:0; border-radius:0 2px 2px 0; background:var(--surface-alt); color:var(--accent); cursor:pointer; }
.result-confirmation-button:hover,.result-confirmation-button:focus-visible { background:var(--accent-faint); }
@media (max-width:420px) { .result-field { grid-template-columns:minmax(0,1fr); gap:4px; } }
</style>