<script setup>
import { computed, reactive, ref } from 'vue';
import { LoaderCircle, Save } from '@lucide/vue';
import InputText from 'openvue/inputtext';
import Tag from 'openvue/tag';
import Textarea from 'openvue/textarea';

const props = defineProps({
    initial: { type: Object, required: true },
    defaults: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
    actionUrl: { type: String, required: true },
    cancelUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    updating: { type: Boolean, default: false },
});

const draft = reactive({
    name: props.initial.name ?? '',
    template: props.initial.template ?? '',
    template_en: props.initial.template_en ?? '',
    bcc: props.initial.bcc ?? '',
});
const submitting = ref(false);
const canSave = computed(() => draft.name.trim() !== '' && draft.name.length <= 128);

function error(field) {
    return props.errors[field]?.[0] ?? '';
}

function submit(event) {
    if (!canSave.value || submitting.value) {
        event.preventDefault();
        return;
    }
    submitting.value = true;
}
</script>

<template>
    <form class="min-w-0" method="POST" :action="actionUrl" @submit="submit">
        <input type="hidden" name="_token" :value="csrf">
        <input v-if="updating" type="hidden" name="_method" value="PUT">
        <fieldset class="form-section">
            <legend>Email template</legend>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div class="field">
                    <label for="email-template-name">Naam</label>
                    <InputText id="email-template-name" v-model="draft.name" name="name" maxlength="128" required autofocus :invalid="Boolean(error('name'))" aria-describedby="email-template-name-error" fluid />
                    <small v-if="error('name')" id="email-template-name-error" class="error-text" role="alert">{{ error('name') }}</small>
                </div>
                <div class="field">
                    <label for="email-template-bcc">BCC</label>
                    <InputText id="email-template-bcc" v-model="draft.bcc" name="bcc" autocomplete="off" :invalid="Boolean(error('bcc'))" aria-describedby="email-template-bcc-hint" fluid />
                    <small v-if="error('bcc')" class="error-text" role="alert">{{ error('bcc') }}</small>
                    <small v-else id="email-template-bcc-hint" class="muted">Meerdere adressen scheiden met een puntkomma. Wordt ingevuld zodra deze template wordt gekozen.</small>
                </div>
            </div>
            <div v-if="updating && defaults.length" class="mt-4 flex flex-wrap items-center gap-2 text-sm">
                <span class="muted">Standaard voor:</span>
                <Tag v-for="label in defaults" :key="label" severity="success" :value="label" />
            </div>
        </fieldset>
        <fieldset class="form-section">
            <legend>Berichttekst</legend>
            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <div class="field">
                    <label for="email-template-body">Email (Nederlands)</label>
                    <Textarea id="email-template-body" v-model="draft.template" name="template" rows="14" autocomplete="off" :invalid="Boolean(error('template'))" fluid />
                    <small v-if="error('template')" class="error-text" role="alert">{{ error('template') }}</small>
                </div>
                <div class="field">
                    <label for="email-template-body-en">Email engelse versie</label>
                    <Textarea id="email-template-body-en" v-model="draft.template_en" name="template_en" rows="14" autocomplete="off" :invalid="Boolean(error('template_en'))" fluid />
                    <small v-if="error('template_en')" class="error-text" role="alert">{{ error('template_en') }}</small>
                </div>
            </div>
            <p class="muted mt-3 text-sm">De tekst wordt als startpunt in het berichtveld gezet en kan bij het versturen nog worden aangepast. Een lege tekst is toegestaan.</p>
        </fieldset>
        <div class="form-actions flex-wrap">
            <button class="button primary" type="submit" :disabled="!canSave || submitting">
                <LoaderCircle v-if="submitting" :size="16" class="spin" aria-hidden="true" /><Save v-else :size="16" aria-hidden="true" />
                {{ updating ? 'Wijzigingen opslaan' : 'Email template aanmaken' }}
            </button>
            <a class="button" :href="cancelUrl">Annuleren</a>
        </div>
    </form>
</template>
