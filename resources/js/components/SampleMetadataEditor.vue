<script setup>
import { reactive, ref, useId } from 'vue';
import { LoaderCircle, Pencil, Plus, Save, Trash2 } from '@lucide/vue';
import Column from 'openvue/column';
import DataTable from 'openvue/datatable';
import AppDialog from './AppDialog.vue';

const props = defineProps({
    metadata: { type: Array, default: () => [] },
    sampleId: { type: [Number, String], required: true },
    endpoint: { type: String, required: true },
    readOnly: Boolean,
});
const emit = defineEmits(['update:metadata']);
const formId = `sample-metadata-form-${useId()}`;
const dialogVisible = ref(false);
const editingId = ref(null);
const pendingDelete = ref(null);
const saving = ref(false);
const deleting = ref(false);
const error = ref('');
const form = reactive({ name: '', value: '' });

function openCreate() {
    editingId.value = null;
    form.name = '';
    form.value = '';
    error.value = '';
    dialogVisible.value = true;
}

function openEdit(metadata) {
    editingId.value = metadata.id;
    form.name = metadata.name;
    form.value = metadata.value;
    error.value = '';
    dialogVisible.value = true;
}

function metadataEndpoint(id = null) {
    const endpoint = props.endpoint.replace('__SAMPLE__', String(props.sampleId));

    return id === null ? endpoint : `${endpoint}/${id}`;
}

function message(payload, fallback) {
    return Object.values(payload.errors ?? {}).flat().join(' ') || payload.message || fallback;
}

async function save() {
    if (saving.value || !form.name.trim()) return;
    saving.value = true;
    error.value = '';

    try {
        const response = await fetch(metadataEndpoint(editingId.value), {
            method: editingId.value === null ? 'POST' : 'PATCH',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ name: form.name, value: form.value }),
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(message(payload, 'Metadata kon niet worden opgeslagen.'));

        const rows = [...props.metadata];
        const index = rows.findIndex((row) => Number(row.id) === Number(payload.data.id));
        if (index === -1) rows.push(payload.data);
        else rows.splice(index, 1, payload.data);
        rows.sort((left, right) => Number(left.meta_order ?? left.id) - Number(right.meta_order ?? right.id));
        emit('update:metadata', rows);
        dialogVisible.value = false;
    } catch (requestError) {
        error.value = requestError.message;
    } finally {
        saving.value = false;
    }
}

async function remove() {
    if (!pendingDelete.value || deleting.value) return;
    deleting.value = true;
    error.value = '';

    try {
        const response = await fetch(metadataEndpoint(pendingDelete.value.id), {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
        });
        if (!response.ok) {
            const payload = await response.json();
            throw new Error(message(payload, 'Metadata kon niet worden verwijderd.'));
        }

        emit('update:metadata', props.metadata.filter((row) => Number(row.id) !== Number(pendingDelete.value.id)));
        pendingDelete.value = null;
    } catch (requestError) {
        error.value = requestError.message;
    } finally {
        deleting.value = false;
    }
}
</script>

<template>
    <div class="sample-metadata-editor">
        <div v-if="!readOnly" class="metadata-toolbar">
            <button class="button primary" type="button" @click="openCreate"><Plus :size="15" />Metadata toevoegen</button>
        </div>
        <p v-if="error" class="metadata-error" role="alert">{{ error }}</p>
        <div v-if="metadata.length" class="table-scroll">
            <DataTable :value="metadata" data-key="id" table-class="data-table metadata-table" :unstyled="true">
                <Column field="name" header="Naam"><template #body="{ data }"><strong>{{ data.name }}</strong></template></Column>
                <Column field="value" header="Waarde"><template #body="{ data }"><span class="metadata-value">{{ data.value }}</span></template></Column>
                <Column v-if="!readOnly" header="Acties">
                    <template #body="{ data }">
                        <span class="metadata-actions">
                            <button class="icon-button" type="button" title="Metadata wijzigen" @click="openEdit(data)"><Pencil :size="14" /></button>
                            <button class="icon-button danger" type="button" title="Metadata verwijderen" @click="pendingDelete = data"><Trash2 :size="14" /></button>
                        </span>
                    </template>
                </Column>
            </DataTable>
        </div>
        <p v-else class="metadata-empty">Geen metadata beschikbaar.</p>

        <AppDialog v-model:visible="dialogVisible" :title="editingId === null ? 'Metadata toevoegen' : 'Metadata wijzigen'">
            <form :id="formId" class="metadata-form" @submit.prevent="save">
                <label><span>Naam</span><input v-model="form.name" type="text" maxlength="128" required autofocus></label>
                <label><span>Waarde</span><textarea v-model="form.value" rows="5"></textarea></label>
            </form>
            <template #footer>
                <button class="button" type="button" :disabled="saving" @click="dialogVisible = false">Annuleren</button>
                <button class="button primary" type="submit" :form="formId" :disabled="saving || !form.name.trim()"><LoaderCircle v-if="saving" class="spin" :size="15" /><Save v-else :size="15" />Opslaan</button>
            </template>
        </AppDialog>

        <AppDialog :visible="pendingDelete !== null" title="Metadata verwijderen" :dismissable-mask="!deleting" :closable="!deleting" @update:visible="pendingDelete = null">
            <p>Metadata <strong>{{ pendingDelete?.name }}</strong> verwijderen?</p>
            <template #footer>
                <button class="button" type="button" :disabled="deleting" @click="pendingDelete = null">Annuleren</button>
                <button class="button danger" type="button" :disabled="deleting" @click="remove"><LoaderCircle v-if="deleting" class="spin" :size="15" /><Trash2 v-else :size="15" />Verwijderen</button>
            </template>
        </AppDialog>
    </div>
</template>

<style scoped>
.sample-metadata-editor { min-width:0; }
.metadata-toolbar { display:flex; justify-content:flex-end; padding-bottom:5px; }
.metadata-toolbar .button { min-height:27px; padding:3px 7px; font-size:11px; }
.metadata-table { min-width:380px; }
.sample-metadata-editor :deep(.metadata-table th),.sample-metadata-editor :deep(.metadata-table td) { padding:6px 7px; line-height:1.25; vertical-align:top; }
.sample-metadata-editor :deep(.metadata-table th:last-child),.sample-metadata-editor :deep(.metadata-table td:last-child) { width:58px; }
.metadata-value { display:block; max-width:460px; white-space:pre-wrap; overflow-wrap:anywhere; }
.metadata-actions { display:flex; justify-content:flex-end; gap:2px; }
.metadata-actions .icon-button { width:24px; height:24px; min-height:24px; }
.metadata-empty { margin:0; padding:5px 2px; color:var(--muted); font-size:11px; }
.metadata-error { margin:0 0 9px; padding:8px; background:#fff1ed; color:#903e32; font-size:11px; }
.metadata-form { display:grid; gap:13px; }
.metadata-form label { display:grid; gap:5px; color:var(--muted); font-size:11px; }
.metadata-form input,.metadata-form textarea { width:100%; min-width:0; padding:7px 8px; border:1px solid #9eabb2; border-radius:2px; background:var(--surface); color:var(--ink); font:inherit; }
.metadata-form textarea { resize:vertical; }
</style>