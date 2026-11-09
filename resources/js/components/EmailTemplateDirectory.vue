<script setup>
import { computed, ref } from 'vue';
import { Check, Pencil, Plus, Search, Star, Trash2 } from '@lucide/vue';
import Column from 'openvue/column';
import DataTable from 'openvue/datatable';
import InputText from 'openvue/inputtext';
import Tag from 'openvue/tag';
import AppConfirmDialog from './AppConfirmDialog.vue';

const props = defineProps({
    templates: { type: Array, default: () => [] },
    types: { type: Array, required: true },
    createUrl: { type: String, required: true },
    editUrl: { type: String, required: true },
    destroyUrl: { type: String, required: true },
    defaultUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});

const filters = ref({ global: { value: '', matchMode: 'contains' } });
const pendingDeletion = ref(null);
const dialogVisible = ref(false);
const submitting = ref(false);
const deletionMessage = computed(() => {
    const template = pendingDeletion.value?.template;
    if (!template) return '';

    const defaults = props.types.filter((type) => template[type.column]).map((type) => type.label.toLocaleLowerCase());
    const warning = defaults.length ? ` Er is daarna geen standaard meer voor: ${defaults.join(', ')}.` : '';

    return `Email template "${template.name}" verwijderen?${warning}`;
});

function url(template, emailTemplate) {
    return template.replace('__EMAIL_TEMPLATE__', String(emailTemplate.id));
}

function confirmDeletion(event, template) {
    pendingDeletion.value = { form: event.currentTarget, template };
    dialogVisible.value = true;
}

function deleteTemplate() {
    if (!pendingDeletion.value || submitting.value) return;
    submitting.value = true;
    pendingDeletion.value.form.submit();
}

function setDefault(event) {
    if (submitting.value) {
        event.preventDefault();
        return;
    }
    submitting.value = true;
}
</script>

<template>
    <div>
        <AppConfirmDialog v-model:visible="dialogVisible" title="Email template verwijderen" danger :message="deletionMessage" confirm-label="Verwijderen" :loading="submitting" :disabled="!pendingDeletion" @confirm="deleteTemplate">
            <template #confirm-icon><Trash2 :size="15" aria-hidden="true" /></template>
        </AppConfirmDialog>
        <div class="page-heading">
            <div><div class="eyebrow">Beheer / Client Portal</div><h1>Uitgaande email</h1></div>
            <a class="button primary" :href="createUrl"><Plus :size="16" aria-hidden="true" />Email toevoegen</a>
        </div>
        <div class="directory-toolbar">
            <label class="directory-search">
                <Search :size="16" aria-hidden="true" />
                <span class="sr-only">Email templates zoeken</span>
                <InputText v-model="filters.global.value" type="search" placeholder="Zoek email template" autocomplete="off" />
            </label>
            <span class="muted tabular-nums">{{ templates.length }} templates</span>
        </div>
        <div class="table-scroll">
            <DataTable v-model:filters="filters" :value="templates" data-key="id" :global-filter-fields="['name', 'id', 'bcc']" sort-field="id" :sort-order="1">
                <Column field="id" header="#" sortable><template #body="{ data }"><span class="muted tabular-nums">{{ data.id }}</span></template></Column>
                <Column field="name" header="Naam" sortable>
                    <template #body="{ data }">
                        <a :href="url(editUrl, data)" class="font-semibold break-words">{{ data.name || 'Naamloos' }}</a>
                        <div v-if="data.bcc" class="muted break-words text-xs">BCC: {{ data.bcc }}</div>
                    </template>
                </Column>
                <Column header="Talen">
                    <template #body="{ data }">
                        <div class="flex items-center gap-1">
                            <Tag value="NL" :severity="data.hasTemplate ? 'info' : 'secondary'" :title="data.hasTemplate ? 'Nederlandse tekst ingesteld' : 'Geen Nederlandse tekst'" />
                            <Tag value="EN" :severity="data.hasTemplateEn ? 'info' : 'secondary'" :title="data.hasTemplateEn ? 'Engelse tekst ingesteld' : 'Geen Engelse tekst'" />
                        </div>
                    </template>
                </Column>
                <Column v-for="type in types" :key="type.value" :header="type.header">
                    <template #body="{ data }">
                        <Tag v-if="data[type.column]" severity="success"><Check :size="12" aria-hidden="true" />Standaard</Tag>
                        <form v-else method="POST" :action="url(defaultUrl, data)" @submit="setDefault">
                            <input type="hidden" name="_token" :value="csrf">
                            <input type="hidden" name="defaultFor" :value="type.value">
                            <button class="icon-button" type="submit" :disabled="submitting" :title="`Instellen als ${type.label.toLocaleLowerCase()}`" :aria-label="`${data.name} instellen als ${type.label.toLocaleLowerCase()}`"><Star :size="16" aria-hidden="true" /></button>
                        </form>
                    </template>
                </Column>
                <Column header="Acties">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <a class="icon-button" :href="url(editUrl, data)" title="Email template bewerken" :aria-label="`Email template ${data.name} bewerken`"><Pencil :size="16" aria-hidden="true" /></a>
                            <form class="inline-form" method="POST" :action="url(destroyUrl, data)" @submit.prevent="confirmDeletion($event, data)">
                                <input type="hidden" name="_token" :value="csrf"><input type="hidden" name="_method" value="DELETE">
                                <button class="icon-button danger" type="submit" title="Email template verwijderen" :aria-label="`Email template ${data.name} verwijderen`" :disabled="submitting"><Trash2 :size="16" aria-hidden="true" /></button>
                            </form>
                        </div>
                    </template>
                </Column>
                <template #empty><span class="empty-state">Geen emails ingesteld</span></template>
            </DataTable>
        </div>
    </div>
</template>
