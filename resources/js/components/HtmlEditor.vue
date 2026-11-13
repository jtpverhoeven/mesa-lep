<script setup>
import { ref, shallowRef } from 'vue';
import { Table2 } from '@lucide/vue';
import Editor from 'openvue/editor';
import Menu from 'openvue/menu';
import { quillTableHtml } from '../confirmationTableHtml.js';

const props = defineProps({ modelValue: { type: String, default: '' } });
const emit = defineEmits(['update:modelValue']);

// The editor only loads the initial HTML; untouched HTML is kept as stored and only edits emit Quill's output.
const editorHtml = quillTableHtml(props.modelValue);
const quill = shallowRef(null);
const menu = ref(null);
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
</script>

<template>
    <div class="table-editor">
        <Editor :model-value="editorHtml" :modules="modules" @text-change="emit('update:modelValue', $event.htmlValue)" @load="quill = $event.instance">
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
    </div>
</template>
