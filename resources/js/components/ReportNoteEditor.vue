<script setup>
import { computed, ref, shallowRef, watch } from 'vue';
import { Check, LoaderCircle } from '@lucide/vue';
import Editor from 'openvue/editor';
import { noteHtml } from '../notes.js';
import { useNoteStore } from '../stores/noteStore.js';
import NoteAuthor from './NoteAuthor.vue';
import NoteTemplatePicker from './NoteTemplatePicker.vue';

const props = defineProps({
    note: { type: Object, default: null },
    endpoint: { type: String, required: true },
    readOnly: Boolean,
    lockTarget: { type: Object, default: null },
});
const emit = defineEmits(['update:note']);
const store = useNoteStore();
const quill = shallowRef(null);
const saved = ref(props.note?.body ?? '');
const draft = ref(saved.value);
const saving = ref(false);
const justSaved = ref(false);
const error = ref('');
const editable = computed(() => store.permissions.editReportNotes && !props.readOnly);
const dirty = computed(() => draft.value !== saved.value);

watch(() => props.note?.body ?? '', (body) => {
    if (!dirty.value && !saving.value) saved.value = draft.value = body;
});

async function save() {
    if (!dirty.value || saving.value || !editable.value) return;
    const body = draft.value;
    saving.value = true;
    justSaved.value = false;
    error.value = '';
    try {
        const note = await store.request('PUT', props.endpoint, { body }, props.lockTarget);
        saved.value = draft.value = note?.body ?? '';
        emit('update:note', note);
        justSaved.value = true;
    } catch (failure) {
        error.value = failure.message;
    } finally {
        saving.value = false;
    }
}

function onFocusOut(event) {
    if (!event.currentTarget.contains(event.relatedTarget)) void save();
}

function insertTemplate(html) {
    if (!quill.value) return;
    const delta = quill.value.clipboard.convert({ html });
    if (quill.value.getText().trim() === '') {
        quill.value.setContents(delta, 'user');
    } else {
        const Delta = quill.value.constructor.import('delta');
        quill.value.updateContents(new Delta().retain(quill.value.getLength()).concat(delta), 'user');
    }
    void save();
}
</script>

<template>
    <div class="note-editor grid gap-1" @focusout="onFocusOut">
        <Editor v-if="editable" v-model="draft" class="note-editor-input" :formats="['bold', 'italic', 'underline', 'list']" @load="quill = $event.instance">
            <template #toolbar>
                <span class="ql-formats">
                    <button class="ql-bold" type="button" title="Vet"></button>
                    <button class="ql-italic" type="button" title="Cursief"></button>
                    <button class="ql-underline" type="button" title="Onderstrepen"></button>
                </span>
                <span class="ql-formats">
                    <button class="ql-list" value="bullet" type="button" title="Opsomming"></button>
                    <button class="ql-list" value="ordered" type="button" title="Genummerde lijst"></button>
                </span>
                <span class="ql-formats"><NoteTemplatePicker :current-html="draft" :disabled="saving" @insert="insertTemplate" /></span>
            </template>
        </Editor>
        <div v-else-if="note" class="note-html" v-html="noteHtml(note.body)"></div>
        <p v-else class="m-0 text-xs text-muted">Geen notitie.</p>
        <div class="flex min-h-5 items-center justify-between gap-2">
            <NoteAuthor v-if="note" :author="note.author" :date="note.updated_at" />
            <span v-else></span>
            <span class="flex shrink-0 items-center text-muted" aria-live="polite">
                <LoaderCircle v-if="saving" class="spin" :size="13" aria-label="Opslaan" />
                <Check v-else-if="justSaved && !dirty" :size="13" class="text-success" aria-label="Opgeslagen" />
            </span>
        </div>
        <p v-if="error" class="m-0 text-xs text-danger" role="alert">{{ error }}</p>
    </div>
</template>
