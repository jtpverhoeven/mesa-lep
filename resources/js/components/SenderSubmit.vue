<script setup>
import { ref } from 'vue';
import { LoaderCircle, Send } from '@lucide/vue';
import Button from 'openvue/button';
import { postJson } from '../senderApi';
import AppDialog from './AppDialog.vue';

const props = defineProps({
    url: { type: String, required: true },
    label: { type: String, required: true },
    payload: { type: Object, required: true },
    hasReceivers: { type: Boolean, required: true },
    validationError: { type: String, default: '' },
    disabled: Boolean,
    redirectUrl: { type: String, default: '' },
});

const confirmVisible = ref(false);
const errorVisible = ref(false);
const progressVisible = ref(false);
const state = ref('idle');
const message = ref('');

function request() {
    if (state.value === 'sending') return;
    if (props.validationError) {
        errorVisible.value = true;
        return;
    }
    if (!props.hasReceivers) {
        confirmVisible.value = true;
        return;
    }
    void send();
}

async function send() {
    confirmVisible.value = false;
    progressVisible.value = true;
    state.value = 'sending';
    message.value = 'Bezig met versturen . . .';
    try {
        const data = await postJson(props.url, props.payload);
        if (data.status === false) throw new Error(data.message);
        state.value = 'done';
        message.value = 'Emails succesvol afgedragen aan portal';
        if (props.redirectUrl) {
            globalThis.location.assign(props.redirectUrl);
        }
    } catch (error) {
        state.value = 'error';
        message.value = error.message || 'Er is mogelijk een fout opgetreden!';
    }
}

function finish() {
    progressVisible.value = false;
    if (state.value !== 'done') return;
    globalThis.location.reload();
}
</script>

<template>
    <Button type="button" :disabled="disabled || state === 'sending'" @click="request">
        <LoaderCircle v-if="state === 'sending'" :size="16" class="spin" aria-hidden="true" /><Send v-else :size="16" aria-hidden="true" />
        {{ label }}
    </Button>

    <AppDialog v-model:visible="errorVisible" title="Verzenden niet mogelijk" width="420px">
        <p role="alert">{{ validationError }}</p>
    </AppDialog>

    <AppDialog v-model:visible="confirmVisible" title="Zonder contact-groep verzenden?" width="460px">
        <p>Geen contact-groep geselecteerd, toch doorgaan met versturen?</p>
        <template #footer>
            <Button type="button" severity="secondary" label="Annuleren" @click="confirmVisible = false" />
            <Button type="button" label="Toch verzenden" @click="send" />
        </template>
    </AppDialog>

    <AppDialog :visible="progressVisible" title="Rapport versturen" width="460px" :dismissable-mask="false" :close-on-escape="false" :closable="state !== 'sending'" @update:visible="finish">
        <p v-if="state === 'error'" class="report-feedback danger" role="alert"><strong>Er is mogelijk een fout opgetreden!</strong><br>{{ message }}</p>
        <p v-else class="report-feedback" role="status">{{ message }}</p>
        <template #footer>
            <Button v-if="state !== 'sending'" type="button" label="Sluiten" @click="finish" />
        </template>
    </AppDialog>
</template>
