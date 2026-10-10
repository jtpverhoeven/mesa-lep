<script setup>
import { computed, ref, useId, watch } from 'vue';
import { Check, LoaderCircle } from '@lucide/vue';
import AppDialog from './AppDialog.vue';

const props = defineProps({
    visible: Boolean,
    title: { type: String, required: true },
    message: { type: String, default: '' },
    danger: Boolean,
    loading: Boolean,
    disabled: Boolean,
    error: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Bevestigen' },
    cancelLabel: { type: String, default: 'Annuleren' },
});
const emit = defineEmits(['update:visible', 'confirm']);
const dialogId = useId();
const formId = `confirm-form-${dialogId}`;
const inputId = `confirm-code-${dialogId}`;
const code = ref('');
const enteredCode = ref('');
const canConfirm = computed(() => props.visible && !props.loading && !props.disabled
    && (!props.danger || (/^\d{4}$/.test(enteredCode.value) && enteredCode.value === code.value)));

watch(() => [props.visible, props.danger], () => {
    enteredCode.value = '';
    code.value = props.visible && props.danger
        ? String(crypto.getRandomValues(new Uint32Array(1))[0] % 10000).padStart(4, '0')
        : '';
}, { immediate: true });

function close() {
    if (!props.loading) emit('update:visible', false);
}

function confirm() {
    if (canConfirm.value) emit('confirm');
}
</script>

<template>
    <AppDialog :visible="visible" :title="title" :dismissable-mask="!loading" :close-on-escape="!loading" :closable="!loading" @update:visible="close">
        <form :id="formId" class="confirm-dialog-form" @submit.prevent="confirm">
            <slot><p v-if="message" class="confirm-dialog-message">{{ message }}</p></slot>
            <div v-if="danger" class="field">
                <label :for="inputId">Bevestigingscode: <strong class="confirm-dialog-code">{{ code }}</strong></label>
                <input :id="inputId" v-model="enteredCode" type="text" inputmode="numeric" autocomplete="off" maxlength="4" pattern="[0-9]{4}" required :disabled="loading || disabled" aria-label="Bevestigingscode">
            </div>
            <p v-if="error" class="confirm-dialog-error" role="alert">{{ error }}</p>
        </form>
        <template #footer>
            <button class="button" type="button" :disabled="loading" @click="close">{{ cancelLabel }}</button>
            <button class="button" :class="danger ? 'danger' : 'primary'" type="submit" :form="formId" :disabled="!canConfirm">
                <LoaderCircle v-if="loading" class="spin" :size="15" />
                <slot v-else name="confirm-icon"><Check :size="15" /></slot>
                {{ confirmLabel }}
            </button>
        </template>
    </AppDialog>
</template>

<style scoped>
.confirm-dialog-form { display:grid; gap:16px; }
.confirm-dialog-message { margin:0; }
.confirm-dialog-code { color:var(--danger, #ad3232); font-variant-numeric:tabular-nums; }
.confirm-dialog-error { margin:0; color:var(--danger, #ad3232); font-size:12px; }
.button.danger { background:var(--danger, #ad3232); border-color:var(--danger, #ad3232); color:#fff; }
</style>