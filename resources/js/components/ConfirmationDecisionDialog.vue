<script setup>
import { Check, X } from '@lucide/vue';
import Dialog from 'openvue/dialog';
import { useConfirmationStore } from '../stores/confirmationStore';

const store = useConfirmationStore();
const dialogPassThrough = {
    mask: { class: 'p-[18px] bg-[rgba(20,35,45,.4)]' },
    root: { class: 'w-[min(430px,100%)] border border-[var(--line)] bg-[var(--surface)] p-5 shadow-[0_12px_30px_rgba(20,35,45,.25)]', 'aria-labelledby': 'confirmation-decision-title' },
    content: { class: 'p-0' },
};

async function decide(value) {
    await store.setDecision(value);
}
</script>

<template>
    <Dialog :visible="!!store.decisionPrompt && !store.readOnly" modal :draggable="false" :dismissable-mask="false" :close-on-escape="false" :closable="false" :show-header="false" :block-scroll="true" :unstyled="true" :pt="dialogPassThrough">
        <div class="grid gap-[18px]">
            <h2 id="confirmation-decision-title" class="m-0 text-[17px]">Bevestiging nodig</h2>
            <p class="m-0 text-[var(--muted)]">Deze analyse kan worden bevestigd. Wil je de bevestiging inschakelen?</p>
            <div class="flex flex-wrap justify-end gap-2"><button class="button" type="button" @click="decide('disable'); store.decisionPrompt = null"><X :size="15" />Niet bevestigen</button><button class="button primary" type="button" autofocus @click="decide('enable')"><Check :size="15" />Bevestiging inschakelen</button></div>
        </div>
    </Dialog>
</template>