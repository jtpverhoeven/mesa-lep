<script setup>
import { ref } from 'vue';
import { Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import Column from 'openvue/column';
import DataTable from 'openvue/datatable';

const props = defineProps({
    printers: { type: Array, default: () => [] },
    types: { type: [Array, Object], required: true },
    createUrl: { type: String, required: true },
    editUrl: { type: String, required: true },
    destroyUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});

const filters = ref({ global: { value: '', matchMode: 'contains' } });
const deletingId = ref(null);

function url(template, printer) {
    return template.replace('__PRINTER__', String(printer.id));
}

function confirmDeletion(event, printer) {
    if (!window.confirm(`Printer "${printer.name}" verwijderen?`)) {
        event.preventDefault();
        return;
    }

    deletingId.value = printer.id;
}
</script>

<template>
    <div class="printer-directory">
        <div class="page-heading">
            <div><div class="eyebrow">Beheer / Labels</div><h1>Printers</h1></div>
            <a class="button primary" :href="createUrl"><Plus :size="16" aria-hidden="true" />Printer toevoegen</a>
        </div>
        <div class="directory-toolbar">
            <label class="directory-search">
                <Search :size="16" aria-hidden="true" />
                <span class="sr-only">Printers zoeken</span>
                <input v-model="filters.global.value" type="search" placeholder="Zoek printer" autocomplete="off">
            </label>
        </div>
        <div class="table-scroll">
            <DataTable v-model:filters="filters" :value="props.printers" data-key="id" :global-filter-fields="['name', 'adres', 'port']" sort-field="name" :sort-order="1" table-class="data-table" :unstyled="true">
                <Column field="name" header="Naam" sortable><template #body="{ data }"><strong>{{ data.name }}</strong></template></Column>
                <Column field="type" header="Printertype" sortable><template #body="{ data }">{{ types[data.type] ?? 'Onbekend' }}</template></Column>
                <Column field="adres" header="Adres" sortable />
                <Column field="port" header="Poort" sortable><template #body="{ data }">{{ data.port || '-' }}</template></Column>
                <Column header="Acties">
                    <template #body="{ data }">
                        <div class="printer-actions">
                            <a class="icon-button" :href="url(editUrl, data)" title="Printer bewerken" :aria-label="`Printer ${data.name} bewerken`"><Pencil :size="16" aria-hidden="true" /></a>
                            <form class="inline-form" method="POST" :action="url(destroyUrl, data)" @submit="confirmDeletion($event, data)">
                                <input type="hidden" name="_token" :value="csrf">
                                <input type="hidden" name="_method" value="DELETE">
                                <button class="icon-button danger" type="submit" title="Printer verwijderen" :aria-label="`Printer ${data.name} verwijderen`" :disabled="deletingId !== null"><Trash2 :size="16" aria-hidden="true" /></button>
                            </form>
                        </div>
                    </template>
                </Column>
                <template #empty><span class="empty-state">Geen printers gevonden.</span></template>
            </DataTable>
        </div>
    </div>
</template>

<style scoped>
.printer-actions { display: flex; align-items: center; gap: 8px; }
</style>