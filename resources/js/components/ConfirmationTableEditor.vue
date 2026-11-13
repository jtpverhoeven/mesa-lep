<script setup>
import { ref, shallowRef } from 'vue';
import { LoaderCircle, Save, Table2 } from '@lucide/vue';
import Editor from 'openvue/editor';
import InputText from 'openvue/inputtext';
import Menu from 'openvue/menu';
import { quillTableHtml } from '../confirmationTableHtml.js';

const props = defineProps({
    initial: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
    actionUrl: { type: String, required: true },
    cancelUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    updating: { type: Boolean, default: false },
});

const name = ref(props.initial.name ?? '');
// Untouched HTML is submitted as stored; only edits replace it with Quill's output.
const html = ref(props.initial.html ?? '');
const editorHtml = quillTableHtml(html.value);
const quill = shallowRef(null);
const menu = ref(null);
const submitting = ref(false);
const modules = { table: true };
const tableModule = () => quill.value?.getModule('table');
const menuItems = [
    { label: 'Tabel invoegen', command: () => tableModule()?.insertTable(3, 3) },
    { separator: true },
    { label: 'Rij erboven', command: () => tableModule()?.insertRowAbove() },
    { label: 'Rij eronder', command: () => tableModule()?.insertRowBelow() },
    { label: 'Kolom links', command: () => tableModule()?.insertColumnLeft() },
    { label: 'Kolom rechts', command: () => tableModule()?.insertColumnRight() },
    { separator: true },
    { label: 'Rij verwijderen', command: () => tableModule()?.deleteRow() },
    { label: 'Kolom verwijderen', command: () => tableModule()?.deleteColumn() },
    { label: 'Tabel verwijderen', command: () => tableModule()?.deleteTable() },
];

function error(field) {
    return props.errors[field]?.[0] ?? '';
}

function submit(event) {
    if (!name.value.trim() || submitting.value) {
        event.preventDefault();
        return;
    }
    submitting.value = true;
}
</script>

<template>
    <form class="grid min-w-0 gap-3" method="POST" :action="actionUrl" @submit="submit">
        <input type="hidden" name="_token" :value="csrf">
        <input v-if="updating" type="hidden" name="_method" value="PUT">
        <input type="hidden" name="html" :value="html">
        <div class="field max-w-md">
            <label for="confirmation-table-name">Naam</label>
            <InputText id="confirmation-table-name" v-model="name" name="name" maxlength="128" required autofocus :invalid="Boolean(error('name'))" fluid />
            <small v-if="error('name')" class="error-text" role="alert">{{ error('name') }}</small>
        </div>
        <div class="table-editor">
            <Editor :model-value="editorHtml" :modules="modules" @text-change="html = $event.htmlValue" @load="quill = $event.instance">
                <template #toolbar>
                    <span class="ql-formats">
                        <select class="ql-header" title="Kop">
                            <option value="1"></option>
                            <option value="2"></option>
                            <option value="0" selected></option>
                        </select>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-bold" type="button" title="Vet"></button>
                        <button class="ql-italic" type="button" title="Cursief"></button>
                        <button class="ql-underline" type="button" title="Onderstrepen"></button>
                    </span>
                    <span class="ql-formats">
                        <select class="ql-color" title="Tekstkleur"></select>
                        <select class="ql-background" title="Markeren"></select>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-list" value="bullet" type="button" title="Opsomming"></button>
                        <button class="ql-list" value="ordered" type="button" title="Genummerde lijst"></button>
                        <select class="ql-align" title="Uitlijning"></select>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-link" type="button" title="Link"></button>
                        <button class="ql-image" type="button" title="Afbeelding"></button>
                        <button class="note-template-button" type="button" title="Tabel" aria-haspopup="menu" @click="menu.toggle($event)"><Table2 :size="16" /></button>
                        <Menu ref="menu" :model="menuItems" popup aria-label="Tabel" />
                    </span>
                    <span class="ql-formats"><button class="ql-clean" type="button" title="Opmaak wissen"></button></span>
                </template>
            </Editor>
            <small v-if="error('html')" class="error-text" role="alert">{{ error('html') }}</small>
        </div>
        <div class="form-actions">
            <button class="button primary" type="submit" :disabled="!name.trim() || submitting">
                <LoaderCircle v-if="submitting" :size="16" class="spin" aria-hidden="true" /><Save v-else :size="16" aria-hidden="true" />Opslaan
            </button>
            <a class="button" :href="cancelUrl">Annuleren</a>
        </div>
    </form>
</template>
