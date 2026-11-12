<script setup>
import { computed, ref } from 'vue';
import { ClipboardList, LoaderCircle, Save, Trash2 } from '@lucide/vue';
import Button from 'openvue/button';
import InputText from 'openvue/inputtext';
import Popover from 'openvue/popover';
import { useNoteStore } from '../stores/noteStore.js';
import AppConfirmDialog from './AppConfirmDialog.vue';

const props = defineProps({
    currentHtml: { type: String, default: '' },
    disabled: Boolean,
});
const emit = defineEmits(['insert']);
const store = useNoteStore();
const popover = ref(null);
const filter = ref('');
const name = ref('');
const busy = ref(false);
const error = ref('');
const removing = ref(null);
const matches = computed(() => store.templates.filter((template) => template.name.toLowerCase().includes(filter.value.trim().toLowerCase())));
const overwrites = computed(() => store.templates.some((template) => template.name.toLowerCase() === name.value.trim().toLowerCase()));
const canSave = computed(() => store.permissions.editReportNotes && name.value.trim() !== '' && props.currentHtml.replace(/<[^>]*>/g, '').trim() !== '');

async function toggle(event) {
    popover.value.toggle(event);
    filter.value = '';
    error.value = '';
    try {
        await store.loadTemplates();
    } catch (failure) {
        error.value = failure.message;
    }
}

function insert(template) {
    emit('insert', template.content);
    popover.value.hide();
}

async function run(action) {
    busy.value = true;
    error.value = '';
    try {
        await action();
    } catch (failure) {
        error.value = failure.message;
    } finally {
        busy.value = false;
    }
}

const save = () => run(async () => {
    await store.saveTemplate(name.value.trim(), props.currentHtml);
    name.value = '';
});

const remove = () => run(async () => {
    await store.deleteTemplate(removing.value.id);
    removing.value = null;
});
</script>

<template>
    <button class="note-template-button" type="button" title="Sjablonen" aria-label="Sjablonen" :disabled="disabled" @click="toggle"><ClipboardList :size="16" /></button>
    <Popover ref="popover">
        <div class="grid w-64 gap-2 text-xs">
            <InputText v-model="filter" size="small" placeholder="Zoek sjabloon" aria-label="Zoek sjabloon" fluid autofocus @keydown.enter.prevent="matches[0] && insert(matches[0])" />
            <ul v-if="matches.length" class="m-0 grid max-h-48 list-none gap-px overflow-y-auto p-0">
                <li v-for="template in matches" :key="template.id" class="flex items-center gap-1">
                    <button class="min-w-0 flex-1 cursor-pointer truncate rounded-control border-0 bg-transparent px-1.5 py-1 text-left text-ink hover:bg-accent-faint hover:text-accent" type="button" :title="template.name" @click="insert(template)">{{ template.name }}</button>
                    <button v-if="store.permissions.editReportNotes" class="icon-button" type="button" title="Sjabloon verwijderen" :aria-label="`Sjabloon ${template.name} verwijderen`" @click="removing = template"><Trash2 :size="13" /></button>
                </li>
            </ul>
            <p v-else class="m-0 text-muted">Geen sjablonen.</p>
            <form v-if="store.permissions.editReportNotes" class="flex gap-1 border-t border-line pt-2" @submit.prevent="save">
                <InputText v-model="name" size="small" maxlength="128" placeholder="Opslaan als sjabloon" aria-label="Sjabloonnaam" fluid />
                <Button type="submit" size="small" :disabled="!canSave || busy" :title="overwrites ? 'Sjabloon overschrijven' : 'Sjabloon opslaan'"><LoaderCircle v-if="busy" class="spin" :size="14" /><Save v-else :size="14" /></Button>
            </form>
            <p v-if="error" class="m-0 text-danger" role="alert">{{ error }}</p>
        </div>
    </Popover>
    <AppConfirmDialog :visible="removing !== null" title="Sjabloon verwijderen" :message="`Sjabloon ${removing?.name ?? ''} verwijderen?`" confirm-label="Verwijderen" :loading="busy" @update:visible="removing = null" @confirm="remove" />
</template>
