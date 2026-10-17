<script setup>
import { computed, ref } from 'vue';
import { FlaskConical, Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import Column from 'openvue/column';
import DataTable from 'openvue/datatable';
import InputText from 'openvue/inputtext';
import AppConfirmDialog from './AppConfirmDialog.vue';

const props = defineProps({
    profiles: { type: Array, default: () => [] },
    createUrl: { type: String, required: true },
    editUrl: { type: String, required: true },
    destroyUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});

const filters = ref({ global: { value: '', matchMode: 'contains' } });
const pendingDeletion = ref(null);
const dialogVisible = ref(false);
const deleting = ref(false);
const deletionMessage = computed(() => pendingDeletion.value ? `Printprofiel "${pendingDeletion.value.profile.name}" verwijderen?` : '');

function url(template, profile) {
    return template.replace('__PRINT_PROFILE__', String(profile.id));
}

function confirmDeletion(event, profile) {
    pendingDeletion.value = { form: event.currentTarget, profile };
    dialogVisible.value = true;
}

function deleteProfile() {
    if (!pendingDeletion.value || deleting.value) return;
    deleting.value = true;
    pendingDeletion.value.form.submit();
}
</script>

<template>
    <div>
        <AppConfirmDialog v-model:visible="dialogVisible" title="Printprofiel verwijderen" danger :message="deletionMessage" confirm-label="Verwijderen" :loading="deleting" :disabled="!pendingDeletion" @confirm="deleteProfile">
            <template #confirm-icon><Trash2 :size="15" aria-hidden="true" /></template>
        </AppConfirmDialog>
        <div class="page-heading">
            <div><div class="eyebrow">Beheer / Labels</div><h1>Printprofielen</h1></div>
            <a class="button primary" :href="createUrl"><Plus :size="16" aria-hidden="true" />Printprofiel toevoegen</a>
        </div>
        <div class="directory-toolbar">
            <label class="directory-search">
                <Search :size="16" aria-hidden="true" />
                <span class="sr-only">Printprofielen zoeken</span>
                <InputText v-model="filters.global.value" type="search" placeholder="Zoek printprofiel" autocomplete="off" />
            </label>
            <span class="muted tabular-nums">{{ profiles.length }} profielen</span>
        </div>
        <div class="table-scroll">
            <DataTable v-model:filters="filters" :value="profiles" data-key="id" :global-filter-fields="['name', 'id']" sort-field="name" :sort-order="1">
                <Column field="id" header="#" sortable><template #body="{ data }"><span class="muted tabular-nums">{{ data.id }}</span></template></Column>
                <Column field="name" header="Printprofiel" sortable><template #body="{ data }"><a :href="url(editUrl, data)" class="font-semibold break-words">{{ data.name }}</a></template></Column>
                <Column header="Acties">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <a class="icon-button" :href="url(editUrl, data)" title="Printprofiel bewerken" :aria-label="`Printprofiel ${data.name} bewerken`"><Pencil :size="16" aria-hidden="true" /></a>
                            <a class="icon-button" :href="`${url(editUrl, data)}#test`" title="Printprofiel testen" :aria-label="`Printprofiel ${data.name} testen`"><FlaskConical :size="16" aria-hidden="true" /></a>
                            <form class="inline-form" method="POST" :action="url(destroyUrl, data)" @submit.prevent="confirmDeletion($event, data)">
                                <input type="hidden" name="_token" :value="csrf"><input type="hidden" name="_method" value="DELETE">
                                <button class="icon-button danger" type="submit" title="Printprofiel verwijderen" :aria-label="`Printprofiel ${data.name} verwijderen`" :disabled="deleting"><Trash2 :size="16" aria-hidden="true" /></button>
                            </form>
                        </div>
                    </template>
                </Column>
                <template #empty><span class="empty-state">Geen printprofielen gevonden.</span></template>
            </DataTable>
        </div>
    </div>
</template>