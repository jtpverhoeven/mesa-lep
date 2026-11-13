<script setup>
import { computed, ref } from 'vue';
import { Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import Column from 'openvue/column';
import DataTable from 'openvue/datatable';
import InputText from 'openvue/inputtext';
import AppConfirmDialog from './AppConfirmDialog.vue';

defineProps({
    worklists: { type: Array, default: () => [] },
    createUrl: { type: String, required: true },
    editUrl: { type: String, required: true },
    destroyUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});

const filters = ref({ global: { value: '', matchMode: 'contains' } });
const pendingDeletion = ref(null);
const dialogVisible = ref(false);
const submitting = ref(false);
const deletionMessage = computed(() => pendingDeletion.value ? `Werklijst "${pendingDeletion.value.worklist.title}" verwijderen?` : '');

function url(template, worklist) {
    return template.replace('__WORKLIST__', String(worklist.id));
}

function confirmDeletion(event, worklist) {
    pendingDeletion.value = { form: event.currentTarget, worklist };
    dialogVisible.value = true;
}

function deleteWorklist() {
    if (!pendingDeletion.value || submitting.value) return;
    submitting.value = true;
    pendingDeletion.value.form.submit();
}
</script>

<template>
    <div>
        <AppConfirmDialog v-model:visible="dialogVisible" title="Werklijst verwijderen" danger :message="deletionMessage" confirm-label="Verwijderen" :loading="submitting" :disabled="!pendingDeletion" @confirm="deleteWorklist">
            <template #confirm-icon><Trash2 :size="15" aria-hidden="true" /></template>
        </AppConfirmDialog>
        <div class="page-heading">
            <div><div class="eyebrow">Beheer / Basis instellingen</div><h1>Werklijsten</h1></div>
            <a class="button primary" :href="createUrl"><Plus :size="16" aria-hidden="true" />Toevoegen</a>
        </div>
        <div class="directory-toolbar">
            <label class="directory-search">
                <Search :size="16" aria-hidden="true" />
                <span class="sr-only">Werklijsten zoeken</span>
                <InputText v-model="filters.global.value" type="search" placeholder="Zoeken" autocomplete="off" />
            </label>
            <span class="muted tabular-nums">{{ worklists.length }} werklijsten</span>
        </div>
        <div class="table-scroll">
            <DataTable v-model:filters="filters" :value="worklists" data-key="id" :global-filter-fields="['title', 'subtitle', 'id']" sort-field="title" :sort-order="1">
                <Column field="id" header="#" sortable><template #body="{ data }"><span class="muted tabular-nums">{{ data.id }}</span></template></Column>
                <Column field="title" header="Titel" sortable>
                    <template #body="{ data }"><a :href="url(editUrl, data)" class="font-semibold break-words">{{ data.title || 'Naamloos' }}</a></template>
                </Column>
                <Column field="subtitle" header="Subtitel" sortable />
                <Column header="Acties">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <a class="icon-button" :href="url(editUrl, data)" title="Bewerken" :aria-label="`${data.title} bewerken`"><Pencil :size="16" aria-hidden="true" /></a>
                            <form class="inline-form" method="POST" :action="url(destroyUrl, data)" @submit.prevent="confirmDeletion($event, data)">
                                <input type="hidden" name="_token" :value="csrf"><input type="hidden" name="_method" value="DELETE">
                                <button class="icon-button danger" type="submit" title="Verwijderen" :aria-label="`${data.title} verwijderen`" :disabled="submitting"><Trash2 :size="16" aria-hidden="true" /></button>
                            </form>
                        </div>
                    </template>
                </Column>
                <template #empty><span class="empty-state">Geen werklijsten</span></template>
            </DataTable>
        </div>
    </div>
</template>
