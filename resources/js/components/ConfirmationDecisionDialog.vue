<script setup>
import { Check, X } from '@lucide/vue';
import Dialog from 'openvue/dialog';
import { watch } from 'vue';
import { sfx } from '../sfx.js';
import { useConfirmationStore } from '../stores/confirmationStore';
import { createAppDialogPassThrough } from '../dialogPassThrough';

const store = useConfirmationStore();
const dialogPassThrough = createAppDialogPassThrough({ width: '430px', titleId: 'confirmation-decision-title', role: 'alertdialog' });

async function decide(value) {
    await store.setDecision(value);
}

watch(
    () => Boolean(store.decisionPrompt && !store.readOnly),
    (isVisible, wasVisible) => {
        if (isVisible && !wasVisible) void sfx.play('confirmations').catch(() => {});
    },
    { immediate: true },
);
</script>

<template>
    <Dialog :visible="!!store.decisionPrompt && !store.readOnly" modal :draggable="false" :dismissable-mask="false" :close-on-escape="false" :closable="false" :show-header="false" :block-scroll="true" :unstyled="true" :pt="dialogPassThrough">
        <div class="grid gap-[18px] p-5">
            <h2 id="confirmation-decision-title" class="m-0 text-[17px]">Bevestiging nodig</h2>
            <p class="m-0 text-[var(--muted)]">Deze analyse kan worden bevestigd. Wil je de bevestiging inschakelen?</p>
            <div class="flex flex-wrap justify-end gap-2"><button class="button" type="button" @click="decide('disable'); store.decisionPrompt = null"><X :size="15" />Niet bevestigen</button><button class="button primary" type="button" autofocus @click="decide('enable')"><Check :size="15" />Bevestiging inschakelen</button></div>
        </div>
    </Dialog>
</template>