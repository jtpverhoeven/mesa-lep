<script setup>
import { ref } from 'vue';
import { Search } from '@lucide/vue';
import Column from 'openvue/column';
import DataTable from 'openvue/datatable';

const props = defineProps({
    portalAssays: { type: Array, default: () => [] },
    editUrl: { type: String, required: true },
    clientsUrl: { type: String, required: true },
});

const filters = ref({ global: { value: '', matchMode: 'contains' } });

function url(template, portalAssay) {
    return template.replace('__PORTAL_ASSAY__', String(portalAssay.id));
}
</script>

<template>
    <div class="portal-assay-directory">
        <div class="directory-toolbar"><label class="directory-search"><Search :size="16" aria-hidden="true" /><span class="sr-only">Portaalanalyses zoeken</span><input v-model="filters.global.value" type="search" placeholder="Zoek portaalanalyses" autocomplete="off"></label></div>
        <div class="table-scroll">
            <DataTable v-model:filters="filters" :value="props.portalAssays" data-key="id" :global-filter-fields="['id', 'common_name', 'common_name_en']" sort-field="common_name" :sort-order="1" paginator :rows="100" :rows-per-page-options="[50, 100, 250]" table-class="data-table" class="portal-assay-data-table" :unstyled="true">
                <Column field="id" header="ID" sortable />
                <Column field="common_name" header="Portaalanalyse" sortable><template #body="{ data }"><strong>{{ data.common_name }}</strong></template></Column>
                <Column field="common_name_en" header="Engels" sortable><template #body="{ data }">{{ data.common_name_en || '-' }}</template></Column>
                <Column field="assays_count" header="Analyses" sortable />
                <Column field="clients_count" header="Klanten" sortable />
                <Column field="selectable" header="Selecteerbaar" sortable><template #body="{ data }">{{ data.selectable ? 'Ja' : 'Nee' }}</template></Column>
                <Column field="alertable" header="Alarm" sortable><template #body="{ data }">{{ data.alertable ? 'Ja' : 'Nee' }}</template></Column>
                <Column header="Acties"><template #body="{ data }"><div class="directory-actions"><a class="text-link" :href="url(editUrl, data)">Bewerken</a><a class="text-link" :href="url(clientsUrl, data)">Klanten</a></div></template></Column>
                <template #empty><span class="empty-state">Geen portaalanalyses gevonden.</span></template>
            </DataTable>
        </div>
    </div>
</template>

<style scoped>
.directory-actions { display:flex; flex-wrap:wrap; gap:10px; }
</style>