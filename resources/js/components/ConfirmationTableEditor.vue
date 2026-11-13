<script setup>
import { ref } from 'vue';
import { LoaderCircle, Save } from '@lucide/vue';
import InputText from 'openvue/inputtext';
import HtmlEditor from './HtmlEditor.vue';

const props = defineProps({
    initial: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
    actionUrl: { type: String, required: true },
    cancelUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    updating: { type: Boolean, default: false },
});

const name = ref(props.initial.name ?? '');
const html = ref(props.initial.html ?? '');
const submitting = ref(false);

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
        <div>
            <HtmlEditor v-model="html" />
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
