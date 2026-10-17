<script setup>
import { ref } from 'vue';
import { Search } from '@lucide/vue';
import Column from 'openvue/column';
import DataTable from 'openvue/datatable';

const props = defineProps({
    assays: { type: Array, default: () => [] },
    editUrl: { type: String, required: true },
});

const filters = ref({
    global: { value: '', matchMode: 'contains' },
});

function assayEditUrl(assay) {
    return props.editUrl.replace('__ASSAY__', String(assay.id));
}
</script>

<template>
    <div class="assay-directory">
        <div class="directory-toolbar">
            <label class="directory-search">
                <Search :size="16" aria-hidden="true" />
                <span class="sr-only">Analyses zoeken</span>
                <input v-model="filters.global.value" type="search" placeholder="Zoek analyses" autocomplete="off">
            </label>
        </div>

        <div class="table-scroll">
            <DataTable
                v-model:filters="filters"
                :value="props.assays"
                data-key="id"
                :global-filter-fields="['id', 'name', 'assayType', 'typeLabel', 'dilution', 'replicates', 'articleCode']"
                sort-field="name"
                :sort-order="1"
                paginator
                :rows="100"
                :rows-per-page-options="[50, 100, 250]"
                table-class="data-table"
                class="assay-data-table"
            >
                <Column field="id" header="ID" sortable />
                <Column field="name" header="Analyse" sortable>
                    <template #body="{ data }"><strong>{{ data.name }}</strong></template>
                </Column>
                <Column field="assayType" header="Analytisch basistype" sortable />
                <Column field="typeLabel" header="Soort" sortable />
                <Column field="dilution" header="Verdunning" sortable />
                <Column field="replicates" header="Replica's" sortable />
                <Column field="articleCode" header="Artikelcode" sortable />
                <Column header="Acties">
                    <template #body="{ data }"><a class="text-link" :href="assayEditUrl(data)">Bewerken</a></template>
                </Column>
                <template #empty><span class="empty-state">Geen analyses gevonden.</span></template>
            </DataTable>
        </div>
    </div>
</template>