<script setup>
import { computed, useId } from 'vue';
import { X } from '@lucide/vue';
import Dialog from 'openvue/dialog';
import { createAppDialogPassThrough } from '../dialogPassThrough';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    visible: Boolean,
    title: { type: String, required: true },
    width: { type: String, default: '520px' },
    dismissableMask: { type: Boolean, default: true },
    closeOnEscape: { type: Boolean, default: true },
    closable: { type: Boolean, default: true },
});
const emit = defineEmits(['update:visible']);
const titleId = `app-dialog-title-${useId()}`;
const passThrough = computed(() => createAppDialogPassThrough({ width: props.width, titleId }));
</script>

<template>
    <Dialog
        v-bind="$attrs"
        :visible="visible"
        modal
        :draggable="false"
        :dismissable-mask="dismissableMask"
        :close-on-escape="closeOnEscape"
        :closable="false"
        :block-scroll="true"
        :unstyled="true"
        :pt="passThrough"
        @update:visible="emit('update:visible', $event)"
    >
        <template #header>
            <slot name="header"><h2 :id="titleId" class="app-dialog-title">{{ title }}</h2></slot>
            <button v-if="closable" class="icon-button" type="button" title="Sluiten" aria-label="Dialoog sluiten" @click="emit('update:visible', false)"><X :size="16" /></button>
        </template>
        <div class="app-dialog-body"><slot /></div>
        <template v-if="$slots.footer" #footer><slot name="footer" /></template>
    </Dialog>
</template>