<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';
import { ArrowLeft, ArrowRight, RefreshCw } from '@lucide/vue';
import Column from 'openvue/column';
import DataTable from 'openvue/datatable';
import AppDialog from './AppDialog.vue';

const props = defineProps({
    visible: Boolean,
    endpoint: { type: String, required: true },
    scope: { type: String, required: true },
    scopeId: { type: Number, default: null },
    sampleId: { type: Number, default: null },
    projectId: { type: Number, default: null },
    types: { type: Array, default: () => [] },
    title: { type: String, default: 'Revisies' },
});
const emit = defineEmits(['update:visible']);
const revisions = ref([]);
const availableTypes = ref([]);
const selectedType = ref('');
const page = ref(1);
const meta = ref({ last_page: 1, total: 0 });
const loading = ref(false);
const error = ref('');
let controller;

async function load() {
    controller?.abort();
    if (!props.visible || !props.scopeId) return;
    const request = new AbortController();
    controller = request;
    loading.value = true;
    error.value = '';
    revisions.value = [];
    const url = new URL(props.endpoint, window.location.origin);
    url.searchParams.set('scope', props.scope);
    url.searchParams.set('id', props.scopeId);
    url.searchParams.set('page', page.value);
    if (props.sampleId) url.searchParams.set('sample_id', props.sampleId);
    if (props.projectId) url.searchParams.set('project_id', props.projectId);
    const types = selectedType.value ? [selectedType.value] : props.types;
    types.forEach((type) => url.searchParams.append('types[]', String(type)));
    try {
        const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: request.signal });
        if (!response.ok) throw new Error(response.status === 403 ? 'Geen toegang tot deze revisies.' : 'Revisies konden niet worden geladen.');
        const data = await response.json();
        if (controller !== request) return;
        revisions.value = data.data;
        availableTypes.value = data.types.filter((type) => !props.types.length || props.types.map(String).includes(type.value));
        meta.value = data.meta;
    } catch (failure) {
        if (request.signal.aborted) return;
        error.value = failure.message;
    } finally {
        if (controller === request) loading.value = false;
    }
}
function changePage(value) {
    page.value = value;
    load();
}
function filter() {
    page.value = 1;
    load();
}
function date(value) {
    if (!value || !/^\d+$/.test(value)) return value || '-';
    const date = new Date(Number(value) * 1000);
    return Number.isNaN(date.getTime()) ? value : date.toLocaleString('nl-NL');
}
watch(() => [props.visible, props.scope, props.scopeId, props.sampleId, props.projectId, ...props.types], () => {
    page.value = 1;
    selectedType.value = '';
    availableTypes.value = [];
    meta.value = { last_page: 1, total: 0 };
    load();
}, { immediate: true });
onBeforeUnmount(() => controller?.abort());
</script>

<template>
    <AppDialog :visible="visible" :title="title" width="1100px" @update:visible="emit('update:visible', $event)">
        <div class="revision-toolbar">
            <label>Type <select v-model="selectedType" :disabled="loading" @change="filter"><option value="">Alle typen</option><option v-for="type in availableTypes" :key="type.value" :value="type.value">{{ type.label }}</option></select></label>
            <button class="icon-button" type="button" title="Revisies vernieuwen" aria-label="Revisies vernieuwen" :disabled="loading" @click="load"><RefreshCw :size="16" /></button>
        </div>
        <p v-if="loading" class="empty-state" role="status">Revisies laden...</p>
        <p v-else-if="error" class="error-text" role="alert">{{ error }} <button class="button" type="button" @click="load"><RefreshCw :size="14" />Opnieuw proberen</button></p>
        <div v-else class="table-scroll revision-table-scroll">
            <DataTable :value="revisions" data-key="id" table-class="data-table revision-table" :unstyled="true">
                <Column header="Datum"><template #body="{ data }"><span class="revision-date">{{ date(data.timestamp) }}</span></template></Column>
                <Column field="user_name" header="Gebruiker" />
                <Column header="Wijziging"><template #body="{ data }"><strong>{{ data.type_label }}</strong><div>{{ data.event }}</div><small v-if="data.sample || data.said">{{ data.sample ? `Monster #${data.sample}` : '' }} {{ data.said ? `Analyse #${data.said}` : '' }}</small></template></Column>
                <Column header="Van"><template #body="{ data }"><span class="revision-value">{{ data.from ?? '-' }}</span></template></Column>
                <Column header="Naar"><template #body="{ data }"><span class="revision-value">{{ data.to ?? '-' }}</span></template></Column>
                <template #empty>Geen revisies gevonden.</template>
            </DataTable>
        </div>
        <template #footer>
            <div class="revision-pagination"><span>{{ meta.total }} revisies</span><div><button class="icon-button" type="button" title="Vorige pagina" aria-label="Vorige pagina" :disabled="loading || page <= 1" @click="changePage(page - 1)"><ArrowLeft :size="16" /></button><span>{{ page }} / {{ meta.last_page }}</span><button class="icon-button" type="button" title="Volgende pagina" aria-label="Volgende pagina" :disabled="loading || page >= meta.last_page" @click="changePage(page + 1)"><ArrowRight :size="16" /></button></div></div>
        </template>
    </AppDialog>
</template>

<style scoped>
.revision-toolbar, .revision-pagination, .revision-pagination > div { display:flex; align-items:center; gap:10px; }
.revision-toolbar { justify-content:space-between; margin-bottom:12px; }
.revision-toolbar label { display:flex; align-items:center; gap:8px; min-width:0; font-size:12px; }
.revision-toolbar select { min-width:0; max-width:240px; padding:6px; font:inherit; color:var(--ink); background:var(--surface); border:1px solid var(--line); border-radius:2px; }
.revision-table-scroll { max-height:60vh; overflow:auto; }
:deep(.revision-table) { table-layout:fixed; min-width:720px; font-size:12px; }
:deep(.revision-table th:nth-child(1)) { width:145px; }
:deep(.revision-table th:nth-child(2)) { width:120px; }
:deep(.revision-table td) { vertical-align:top; white-space:pre-wrap; overflow-wrap:anywhere; }
:deep(.revision-table small) { display:block; margin-top:5px; color:var(--muted); }
.revision-date { white-space:nowrap; }
.revision-value { white-space:pre-wrap; overflow-wrap:anywhere; }
.revision-pagination { justify-content:space-between; width:100%; font-size:12px; }
.revision-pagination > div > span { min-width:45px; text-align:center; }
</style>