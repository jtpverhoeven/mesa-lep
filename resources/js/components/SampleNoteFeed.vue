<script setup>
import { computed, nextTick, ref } from 'vue';
import { AtSign, LoaderCircle, Send, Trash2 } from '@lucide/vue';
import Button from 'openvue/button';
import Textarea from 'openvue/textarea';
import Avatar from 'openvue/avatar';
import { feedBodyHtml, noteHtml } from '../notes.js';
import { useNoteStore } from '../stores/noteStore.js';
import AppConfirmDialog from './AppConfirmDialog.vue';
import NoteAuthor from './NoteAuthor.vue';

const props = defineProps({
    notes: { type: Array, default: () => [] },
    sampleId: { type: Number, required: true },
});
const emit = defineEmits(['update:notes']);
const store = useNoteStore();
const draft = ref('');
const mentions = ref([]);
const posting = ref(false);
const error = ref('');
const field = ref(null);
const query = ref(null);
const activeIndex = ref(0);
const deleting = ref(null);
const deletingBusy = ref(false);
const canAdd = computed(() => store.permissions.addSampleNotes);
const suggestions = computed(() => {
    if (query.value === null) return [];
    const needle = query.value.toLowerCase();

    return store.users.filter((user) => user.name.toLowerCase().includes(needle)).slice(0, 6);
});

function textarea() {
    return field.value?.$el ?? null;
}

function trackMention() {
    const element = textarea();
    const before = draft.value.slice(0, element?.selectionStart ?? draft.value.length);
    const match = before.match(/(?:^|\s)@([^@\n]{0,40})$/);
    query.value = match ? match[1] : null;
    activeIndex.value = 0;
    if (match) void store.loadUsers();
}

async function insertAt() {
    const element = textarea();
    const position = element.selectionStart ?? draft.value.length;
    const before = draft.value.slice(0, position);
    const prefix = before === '' || /\s$/.test(before) ? '' : ' ';
    draft.value = `${before}${prefix}@${draft.value.slice(position)}`;
    await nextTick();
    element.focus();
    element.setSelectionRange(position + prefix.length + 1, position + prefix.length + 1);
    trackMention();
}

async function pick(user) {
    const element = textarea();
    const cursor = element.selectionStart;
    const start = draft.value.slice(0, cursor).lastIndexOf('@');
    const inserted = `@${user.name} `;
    draft.value = draft.value.slice(0, start) + inserted + draft.value.slice(cursor);
    if (!mentions.value.some((id) => id === user.id)) mentions.value.push(user.id);
    query.value = null;
    await nextTick();
    element.focus();
    element.selectionStart = element.selectionEnd = start + inserted.length;
}

function onKeydown(event) {
    if (suggestions.value.length) {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const step = event.key === 'ArrowDown' ? 1 : -1;
            activeIndex.value = (activeIndex.value + step + suggestions.value.length) % suggestions.value.length;
            return;
        }
        if (event.key === 'Enter' || event.key === 'Tab') {
            event.preventDefault();
            void pick(suggestions.value[activeIndex.value]);
            return;
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            query.value = null;
            return;
        }
    }
    if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
        event.preventDefault();
        void submit();
    }
}

async function submit() {
    const body = draft.value.trim();
    if (!body || posting.value || !canAdd.value) return;
    posting.value = true;
    error.value = '';
    try {
        emit('update:notes', await store.request('POST', store.endpoint('sampleNotes', { sample: props.sampleId }), { body, mentions: mentions.value }));
        draft.value = '';
        mentions.value = [];
        query.value = null;
    } catch (failure) {
        error.value = failure.message;
    } finally {
        posting.value = false;
    }
}

async function confirmDelete() {
    deletingBusy.value = true;
    error.value = '';
    try {
        emit('update:notes', await store.request('DELETE', store.endpoint('sampleNote', { sample: props.sampleId, note: deleting.value.id })));
        deleting.value = null;
    } catch (failure) {
        error.value = failure.message;
    } finally {
        deletingBusy.value = false;
    }
}
</script>

<template>
    <div class="grid gap-2.5">
        <form v-if="canAdd" class="grid gap-1.5" @submit.prevent="submit">
            <div class="relative">
                <Textarea ref="field" v-model="draft" rows="2" auto-resize fluid placeholder="Notitie toevoegen" aria-label="Notitie toevoegen" :disabled="posting" @input="trackMention" @click="trackMention" @keyup.left="trackMention" @keyup.right="trackMention" @keydown="onKeydown" />
                <ul v-if="suggestions.length" class="absolute inset-x-0 top-full z-20 m-0 mt-0.5 list-none overflow-hidden rounded-control border border-control-border bg-surface p-0 shadow-md" role="listbox">
                    <li v-for="(user, index) in suggestions" :key="user.id" role="option" :aria-selected="index === activeIndex" class="flex cursor-pointer items-center gap-1.5 px-2 py-1 text-xs" :class="index === activeIndex ? 'bg-accent-faint text-accent' : 'hover:bg-surface-alt'" @mousedown.prevent="pick(user)">
                        <Avatar :image="user.avatar_url ?? undefined" :label="user.avatar_url ? undefined : user.name.charAt(0).toUpperCase()" shape="circle" class="size-5 text-[10px]" aria-hidden="true" />{{ user.name }}
                    </li>
                </ul>
            </div>
            <div class="flex items-center justify-between gap-2">
                <Button type="button" severity="secondary" variant="text" size="small" aria-label="Gebruiker taggen" title="Gebruiker taggen" :disabled="posting" @click="insertAt"><AtSign :size="14" /></Button>
                <Button type="submit" size="small" :disabled="!draft.trim() || posting"><LoaderCircle v-if="posting" class="spin" :size="14" /><Send v-else :size="14" />Plaatsen</Button>
            </div>
        </form>
        <p v-if="error" class="m-0 text-xs text-danger" role="alert">{{ error }}</p>
        <ul v-if="notes.length" class="m-0 grid list-none gap-2 p-0">
            <li v-for="note in notes" :key="note.id" class="grid gap-1 border-l-2 border-line pl-2.5">
                <div class="flex items-center justify-between gap-2">
                    <NoteAuthor :author="note.author" :date="note.created_at" />
                    <button v-if="note.is_own && canAdd" class="icon-button" type="button" title="Notitie verwijderen" aria-label="Notitie verwijderen" @click="deleting = note"><Trash2 :size="13" /></button>
                </div>
                <div class="note-feed-body" v-html="noteHtml(feedBodyHtml(note))"></div>
            </li>
        </ul>
        <p v-else class="m-0 text-xs text-muted">Geen notities.</p>
        <AppConfirmDialog :visible="deleting !== null" title="Notitie verwijderen" message="Deze notitie verwijderen?" confirm-label="Verwijderen" :loading="deletingBusy" @update:visible="deleting = null" @confirm="confirmDelete" />
    </div>
</template>
