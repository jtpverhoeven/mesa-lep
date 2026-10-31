<script setup>
import { computed, ref, watch } from 'vue';
import Avatar from 'openvue/avatar';
import Button from 'openvue/button';
import { KeyRound, LockKeyhole } from '@lucide/vue';
import { sfx } from '../sfx.js';
import { useKeyringStore } from '../stores/keyringStore';
import AppConfirmDialog from './AppConfirmDialog.vue';

const props = defineProps({
    type: { type: String, required: true },
    id: { type: [Number, String], default: null },
});
const keyrings = useKeyringStore();
const labels = { sample: 'Dit monster', project: 'Dit project', export: 'Deze export' };
const entry = computed(() => (props.id ? keyrings.entry(props.type, props.id) : null));
const status = computed(() => entry.value?.status ?? null);
const locked = computed(() => Boolean(status.value?.locked));
const label = computed(() => labels[props.type] ?? 'Dit onderdeel');
const holderName = computed(() => status.value?.locked_by_name || 'een andere gebruiker');
const confirmVisible = ref(false);
const stealing = ref(false);
const stealError = ref('');

watch(locked, (isLocked, wasLocked) => {
    if (!isLocked || wasLocked) return;
    try {
        void sfx.play('warning').catch((error) => console.warn('Vergrendelingswaarschuwing kon niet worden afgespeeld.', error));
    } catch (error) {
        console.warn('Vergrendelingswaarschuwing kon niet worden afgespeeld.', error);
    }
}, { immediate: true });

async function steal() {
    stealing.value = true;
    stealError.value = '';
    const result = await keyrings.steal(props.type, props.id);
    stealing.value = false;
    if (result?.lock_placed) {
        confirmVisible.value = false;
    } else {
        stealError.value = entry.value?.error || 'De vergrendeling kon niet worden overgenomen.';
    }
}
</script>

<template>
    <div v-if="locked" class="keyring-notice" :class="{ stolen: entry.stolenBy }" role="status" aria-live="polite">
        <Avatar :image="status.locked_by_avatar || undefined" :label="status.locked_by_avatar ? undefined : holderName.charAt(0).toUpperCase()" shape="circle" aria-hidden="true" />
        <div class="keyring-notice-text">
            <strong v-if="entry.stolenBy"><LockKeyhole :size="15" aria-hidden="true" />{{ label }} is overgenomen door {{ entry.stolenBy }}.</strong>
            <strong v-else><LockKeyhole :size="15" aria-hidden="true" />{{ label }} is in gebruik door {{ holderName }}.</strong>
            <span v-if="entry.stolenBy">Niet-opgeslagen invoer blijft zichtbaar, maar kan niet meer worden opgeslagen.</span>
            <span v-else>U kunt meekijken; wijzigen is mogelijk zodra de vergrendeling vrijkomt.</span>
        </div>
        <Button v-if="keyrings.config.can_steal" class="keyring-notice-action" label="Overnemen" severity="secondary" size="small" :loading="stealing" @click="confirmVisible = true">
            <template #icon><KeyRound :size="15" /></template>
        </Button>
        <AppConfirmDialog v-model:visible="confirmVisible" title="Vergrendeling overnemen" confirm-label="Overnemen" :loading="stealing" :error="stealError" @confirm="steal">
            <p class="m-0">{{ label }} is in gebruik door {{ holderName }}. Na het overnemen kan {{ holderName }} geen wijzigingen meer opslaan. Deze actie wordt vastgelegd.</p>
            <template #confirm-icon><KeyRound :size="15" /></template>
        </AppConfirmDialog>
    </div>
    <p v-else-if="entry?.error && !status" class="keyring-notice-error" role="alert">{{ entry.error }}</p>
</template>
