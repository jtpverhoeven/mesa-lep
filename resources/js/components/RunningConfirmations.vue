<script setup>
import { ref } from 'vue';
import { FlaskConical, FolderOpen, Search } from '@lucide/vue';
import Column from 'openvue/column';
import DataTable from 'openvue/datatable';

const props = defineProps({
    confirmations: { type: Array, default: () => [] },
    lookupUrl: { type: String, required: true },
    projectUrl: { type: String, default: '' },
    clientUrl: { type: String, default: '' },
});

const filters = ref({ global: { value: '', matchMode: 'contains' } });

function url(template, placeholder, value) {
    return template.replace(placeholder, encodeURIComponent(String(value)));
}
</script>

<template>
    <div class="directory-toolbar">
        <label class="directory-search">
            <Search :size="16" aria-hidden="true" />
            <span class="sr-only">Lopende bevestigingen zoeken</span>
            <input v-model="filters.global.value" type="search" placeholder="Zoek monster, klant of analyse" autocomplete="off">
        </label>
    </div>
    <div class="table-scroll">
        <DataTable v-model:filters="filters" :value="props.confirmations" data-key="id" :global-filter-fields="['barcode', 'description', 'client', 'assay_name']" sort-field="assay_name" :sort-order="1">
            <Column field="barcode" header="Barcode" sortable><template #body="{ data }"><strong>{{ data.barcode }}</strong></template></Column>
            <Column field="description" header="Omschrijving" sortable />
            <Column field="client" header="Klant" sortable>
                <template #body="{ data }">
                    <a v-if="clientUrl && data.client_id" :href="url(clientUrl, '__CLIENT__', data.client_id)">{{ data.client }}</a>
                    <template v-else>{{ data.client ?? '-' }}</template>
                </template>
            </Column>
            <Column field="assay_name" header="Analyse" sortable />
            <Column header="Acties">
                <template #body="{ data }">
                    <div class="flex items-center gap-2">
                        <a class="button" :href="url(lookupUrl, '__BARCODE__', data.barcode_specific)"><FlaskConical :size="16" aria-hidden="true" />Open monster</a>
                        <a v-if="projectUrl && data.project" class="button" :href="url(projectUrl, '__PROJECT__', data.project)"><FolderOpen :size="16" aria-hidden="true" />Open project</a>
                    </div>
                </template>
            </Column>
            <template #empty><span class="empty-state">Geen lopende bevestigingen.</span></template>
        </DataTable>
    </div>
</template>
