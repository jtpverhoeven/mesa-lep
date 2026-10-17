<script setup>
import { ref } from 'vue';
import { Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import Column from 'openvue/column';
import DataTable from 'openvue/datatable';

const props = defineProps({
    labelDesigns: { type: Array, default: () => [] },
    createUrl: { type: String, required: true },
    editUrl: { type: String, required: true },
    destroyUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});

const filters = ref({ global: { value: '', matchMode: 'contains' } });
const deletingId = ref(null);

function url(template, labelDesign) {
    return template.replace('__LABEL_DESIGN__', String(labelDesign.id));
}

function confirmDeletion(event, labelDesign) {
    if (!window.confirm(`Labelontwerp "${labelDesign.name}" verwijderen?`)) {
        event.preventDefault();
        return;
    }

    deletingId.value = labelDesign.id;
}
</script>

<template>
    <div class="label-design-directory">
        <div class="page-heading">
            <div><div class="eyebrow">Beheer / Labels</div><h1>Labelontwerpen</h1></div>
            <a class="button primary" :href="createUrl"><Plus :size="16" aria-hidden="true" />Labelontwerp toevoegen</a>
        </div>
        <div class="directory-toolbar">
            <label class="directory-search">
                <Search :size="16" aria-hidden="true" />
                <span class="sr-only">Labelontwerpen zoeken</span>
                <input v-model="filters.global.value" type="search" placeholder="Zoek labelontwerp" autocomplete="off">
            </label>
        </div>
        <div class="table-scroll">
            <DataTable v-model:filters="filters" :value="props.labelDesigns" data-key="id" :global-filter-fields="['name', 'printer.name']" sort-field="name" :sort-order="1">
                <Column field="name" header="Naam" sortable><template #body="{ data }"><strong>{{ data.name }}</strong></template></Column>
                <Column field="default_sample" header="Standaard monsters" sortable><template #body="{ data }">{{ data.default_sample ? 'Ja' : 'Nee' }}</template></Column>
                <Column field="default_analysis" header="Standaard analyses" sortable><template #body="{ data }">{{ data.default_analysis ? 'Ja' : 'Nee' }}</template></Column>
                <Column field="printer.name" header="Standaardprinter" sortable><template #body="{ data }">{{ data.printer?.name || '-' }}</template></Column>
                <Column header="Acties">
                    <template #body="{ data }">
                        <div class="label-design-actions">
                            <a class="icon-button" :href="url(editUrl, data)" title="Labelontwerp bewerken" :aria-label="`Labelontwerp ${data.name} bewerken`"><Pencil :size="16" aria-hidden="true" /></a>
                            <form class="inline-form" method="POST" :action="url(destroyUrl, data)" @submit="confirmDeletion($event, data)">
                                <input type="hidden" name="_token" :value="csrf">
                                <input type="hidden" name="_method" value="DELETE">
                                <button class="icon-button danger" type="submit" title="Labelontwerp verwijderen" :aria-label="`Labelontwerp ${data.name} verwijderen`" :disabled="deletingId !== null"><Trash2 :size="16" aria-hidden="true" /></button>
                            </form>
                        </div>
                    </template>
                </Column>
                <template #empty><span class="empty-state">Geen labelontwerpen gevonden.</span></template>
            </DataTable>
        </div>
    </div>
</template>

<style scoped>
.label-design-actions { display: flex; align-items: center; gap: 8px; }
</style>