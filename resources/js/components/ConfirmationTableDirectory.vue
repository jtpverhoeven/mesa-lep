<script setup>
import { computed, ref } from 'vue';
import { Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import Column from 'openvue/column';
import DataTable from 'openvue/datatable';
import InputText from 'openvue/inputtext';
import AppConfirmDialog from './AppConfirmDialog.vue';

defineProps({
    tables: { type: Array, default: () => [] },
    createUrl: { type: String, required: true },
    editUrl: { type: String, required: true },
    destroyUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});

const filters = ref({ global: { value: '', matchMode: 'contains' } });
const pendingDeletion = ref(null);
const dialogVisible = ref(false);
const submitting = ref(false);
const deletionMessage = computed(() => {
    const table = pendingDeletion.value?.table;
    if (!table) return '';

    const usage = table.assays_count ? ` Wordt gebruikt door ${table.assays_count} ${table.assays_count === 1 ? 'analyse' : 'analyses'}.` : '';

    return `Bevestigingstabel "${table.name}" verwijderen?${usage}`;
});

function url(template, table) {
    return template.replace('__TABLE__', String(table.id));
}

function confirmDeletion(event, table) {
    pendingDeletion.value = { form: event.currentTarget, table };
    dialogVisible.value = true;
}

function deleteTable() {
    if (!pendingDeletion.value || submitting.value) return;
    submitting.value = true;
    pendingDeletion.value.form.submit();
}
</script>

<template>
    <div>
        <AppConfirmDialog v-model:visible="dialogVisible" title="Bevestigingstabel verwijderen" danger :message="deletionMessage" confirm-label="Verwijderen" :loading="submitting" :disabled="!pendingDeletion" @confirm="deleteTable">
            <template #confirm-icon><Trash2 :size="15" aria-hidden="true" /></template>
        </AppConfirmDialog>
        <div class="page-heading">
            <div><div class="eyebrow">Beheer / Media &amp; bevestigingen</div><h1>Bevestigingstabellen</h1></div>
            <a class="button primary" :href="createUrl"><Plus :size="16" aria-hidden="true" />Toevoegen</a>
        </div>
        <div class="directory-toolbar">
            <label class="directory-search">
                <Search :size="16" aria-hidden="true" />
                <span class="sr-only">Bevestigingstabellen zoeken</span>
                <InputText v-model="filters.global.value" type="search" placeholder="Zoeken" autocomplete="off" />
            </label>
            <span class="muted tabular-nums">{{ tables.length }} tabellen</span>
        </div>
        <div class="table-scroll">
            <DataTable v-model:filters="filters" :value="tables" data-key="id" :global-filter-fields="['name', 'id']" sort-field="name" :sort-order="1">
                <Column field="id" header="#" sortable><template #body="{ data }"><span class="muted tabular-nums">{{ data.id }}</span></template></Column>
                <Column field="name" header="Naam" sortable>
                    <template #body="{ data }"><a :href="url(editUrl, data)" class="font-semibold break-words">{{ data.name || 'Naamloos' }}</a></template>
                </Column>
                <Column field="assays_count" header="Analyses" sortable><template #body="{ data }"><span class="tabular-nums">{{ data.assays_count }}</span></template></Column>
                <Column header="Acties">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <a class="icon-button" :href="url(editUrl, data)" title="Bewerken" :aria-label="`${data.name} bewerken`"><Pencil :size="16" aria-hidden="true" /></a>
                            <form class="inline-form" method="POST" :action="url(destroyUrl, data)" @submit.prevent="confirmDeletion($event, data)">
                                <input type="hidden" name="_token" :value="csrf"><input type="hidden" name="_method" value="DELETE">
                                <button class="icon-button danger" type="submit" title="Verwijderen" :aria-label="`${data.name} verwijderen`" :disabled="submitting"><Trash2 :size="16" aria-hidden="true" /></button>
                            </form>
                        </div>
                    </template>
                </Column>
                <template #empty><span class="empty-state">Geen bevestigingstabellen</span></template>
            </DataTable>
        </div>
    </div>
</template>
