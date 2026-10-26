<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';
import { ArrowLeft, ArrowRight, RefreshCw } from '@lucide/vue';
import Column from 'openvue/column';
import DataTable from 'openvue/datatable';
import AppDialog from './AppDialog.vue';

const props = defineProps({
    visible: Boolean,
    endpoint: { type: String, required: true },
});
const emit = defineEmits(['update:visible']);
const reports = ref([]);
const page = ref(1);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(false);
const error = ref('');
let controller;

async function load() {
    controller?.abort();
    if (!props.visible || !props.endpoint) return;

    const request = new AbortController();
    controller = request;
    loading.value = true;
    error.value = '';
    reports.value = [];
    const url = new URL(props.endpoint, window.location.origin);
    url.searchParams.set('page', String(page.value));

    try {
        const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: request.signal });
        if (!response.ok) {
            throw new Error(response.status === 403 ? 'Geen toegang tot deze rapportages.' : 'Rapportages konden niet worden geladen.');
        }
        const data = await response.json();
        if (controller !== request) return;
        reports.value = data.data;
        meta.value = data.meta;
    } catch (failure) {
        if (request.signal.aborted) return;
        error.value = failure instanceof Error ? failure.message : 'Rapportages konden niet worden geladen.';
    } finally {
        if (controller === request) loading.value = false;
    }
}

function changePage(value) {
    page.value = value;
    load();
}

watch(() => [props.visible, props.endpoint], () => {
    page.value = 1;
    reports.value = [];
    meta.value = { current_page: 1, last_page: 1, total: 0 };
    load();
}, { immediate: true });

onBeforeUnmount(() => controller?.abort());
</script>

<template>
    <AppDialog :visible="visible" title="Rapportages" width="1100px" @update:visible="emit('update:visible', $event)">
        <div class="report-overview-content">
            <div class="report-overview-toolbar">
                <span>{{ meta.total }} rapportages</span>
                <button class="icon-button" type="button" title="Rapportages vernieuwen" aria-label="Rapportages vernieuwen" :disabled="loading" @click="load"><RefreshCw :size="16" /></button>
            </div>
            <p v-if="loading" class="empty-state" role="status">Rapportages laden...</p>
            <p v-else-if="error" class="error-text" role="alert">{{ error }} <button class="button" type="button" @click="load"><RefreshCw :size="14" />Opnieuw proberen</button></p>
            <div v-else class="table-scroll report-overview-table-scroll">
                <DataTable :value="reports" data-key="id" table-class="report-overview-table">
                    <Column field="reference" header="Project" />
                    <Column field="revision" header="Revisie" />
                    <Column field="print_version" header="Print versie" />
                    <Column field="date" header="Datum" />
                    <Column field="type" header="Type" />
                    <Column header="Acties">
                        <template #body="{ data }">
                            <a v-if="data.url" class="button" :href="data.url" target="_blank" rel="noopener" :aria-label="`Rapport ${data.reference} bekijken`">Bekijken</a>
                            <span v-else class="muted">PDF niet beschikbaar</span>
                        </template>
                    </Column>
                    <template #empty>Geen rapportages gevonden.</template>
                </DataTable>
            </div>
        </div>
        <template #footer>
            <div class="report-overview-pagination">
                <span>{{ meta.total }} rapportages</span>
                <div>
                    <button class="icon-button" type="button" title="Vorige pagina" aria-label="Vorige pagina" :disabled="loading || page <= 1" @click="changePage(page - 1)"><ArrowLeft :size="16" /></button>
                    <span>{{ page }} / {{ meta.last_page }}</span>
                    <button class="icon-button" type="button" title="Volgende pagina" aria-label="Volgende pagina" :disabled="loading || page >= meta.last_page" @click="changePage(page + 1)"><ArrowRight :size="16" /></button>
                </div>
            </div>
        </template>
    </AppDialog>
</template>

<style scoped>
.report-overview-content, .report-overview-toolbar, .report-overview-pagination, .report-overview-pagination > div { display:flex; align-items:center; gap:10px; }
.report-overview-content { flex-direction:column; }
.report-overview-toolbar, .report-overview-pagination { justify-content:space-between; width:100%; font-size:12px; }
.report-overview-table-scroll { max-height:60vh; overflow:auto; }
:deep(.report-overview-table) { table-layout:fixed; min-width:760px; font-size:12px; }
:deep(.report-overview-table th:nth-child(1)) { width:260px; }
:deep(.report-overview-table th:nth-child(2)), :deep(.report-overview-table th:nth-child(3)) { width:100px; }
:deep(.report-overview-table th:nth-child(4)) { width:120px; }
:deep(.report-overview-table th:nth-child(5)) { width:160px; }
:deep(.report-overview-table td) { vertical-align:top; overflow-wrap:anywhere; }
.report-overview-pagination > div > span { min-width:45px; text-align:center; }
</style>
