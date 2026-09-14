<script setup>
import { Check, X } from '@lucide/vue';
import { useConfirmationStore } from '../stores/confirmationStore';

const store = useConfirmationStore();

async function decide(value) {
    await store.setDecision(value);
}
</script>

<template>
    <div v-if="store.decisionPrompt && !store.readOnly" class="confirmation-decision-backdrop" role="presentation">
        <section class="confirmation-decision" role="dialog" aria-modal="true" aria-labelledby="confirmation-decision-title">
            <h2 id="confirmation-decision-title">Bevestiging nodig</h2>
            <p>Deze analyse kan worden bevestigd. Wil je de bevestiging inschakelen?</p>
            <div><button class="button" type="button" @click="decide('disable'); store.decisionPrompt = null"><X :size="15" />Niet bevestigen</button><button class="button primary" type="button" @click="decide('enable')"><Check :size="15" />Bevestiging inschakelen</button></div>
        </section>
    </div>
</template>

<style scoped>
.confirmation-decision-backdrop { position:fixed; z-index:80; inset:0; display:grid; place-items:center; padding:18px; background:rgba(20,35,45,.4); }
.confirmation-decision { width:min(430px,100%); padding:20px; border:1px solid var(--line); background:var(--surface); box-shadow:0 12px 30px rgba(20,35,45,.25); }
.confirmation-decision h2 { margin:0 0 8px; font-size:17px; }
.confirmation-decision p { margin:0 0 18px; color:var(--muted); }
.confirmation-decision > div { display:flex; justify-content:flex-end; flex-wrap:wrap; gap:8px; }
</style>