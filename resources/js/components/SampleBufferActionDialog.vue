<script setup>
import { computed, reactive, watch } from 'vue';
import { LoaderCircle, Save } from '@lucide/vue';
import AppDialog from './AppDialog.vue';

const props = defineProps({ visible: Boolean, action: String, samplingMethods: Array, loading: Boolean });
const emit = defineEmits(['update:visible', 'submit']);
const values = reactive({ date: '', sampling_method: '', storage: '4', receive_date: '', receive_time: '', meta: {} });
const titles = { commit: 'Monsterontvangst', sampling_date: 'Bemonsterdatum wijzigen', sampling_method: 'Bemonsteringsprocedure wijzigen', move_to_tht: 'Naar THT-lijst verplaatsen', tht_date: 'Inzetdatum wijzigen', storage: 'Bewaartemperatuur wijzigen', receive: 'Ontvangst wijzigen', metadata: 'Metadata wijzigen' };
const needsReceipt = computed(() => ['commit', 'receive'].includes(props.action));
const needsDate = computed(() => ['sampling_date', 'move_to_tht', 'tht_date'].includes(props.action));
const needsStorage = computed(() => ['move_to_tht', 'storage'].includes(props.action));
watch(() => props.visible, (visible) => {
    if (!visible) return;
    const now = new Date();
    values.date = now.toISOString().slice(0, 10);
    values.receive_date = now.toISOString().slice(0, 10);
    values.receive_time = now.toTimeString().slice(0, 5);
    values.sampling_method = '';
    values.storage = '4';
});
</script>

<template>
    <AppDialog :visible="visible" :title="titles[action]" @update:visible="emit('update:visible', $event)">
        <form id="sample-buffer-action-form" class="form-grid buffer-dialog-form" @submit.prevent="emit('submit', { ...values })">
            <div v-if="needsReceipt" class="field"><label for="buffer-receive-date">Ontvangstdatum</label><input id="buffer-receive-date" v-model="values.receive_date" type="date" required></div>
            <div v-if="needsReceipt" class="field"><label for="buffer-receive-time">Ontvangsttijd</label><input id="buffer-receive-time" v-model="values.receive_time" type="time" required></div>
            <div v-if="needsDate" class="field wide"><label for="buffer-action-date">{{ action === 'sampling_date' ? 'Bemonsterdatum' : 'Inzetdatum' }}</label><input id="buffer-action-date" v-model="values.date" type="date" required></div>
            <div v-if="action === 'sampling_method'" class="field wide"><label for="buffer-sampling-method">Bemonsteringsprocedure</label><select id="buffer-sampling-method" v-model="values.sampling_method" required><option value="" disabled>Selecteer een procedure</option><option v-for="method in samplingMethods" :key="method.id" :value="method.id">{{ method.name }}</option></select></div>
            <div v-if="needsStorage" class="field wide"><label for="buffer-storage">Bewaartemperatuur</label><select id="buffer-storage" v-model="values.storage"><option value="4">+3°C</option><option value="0">+4°C</option><option value="1">+7°C</option><option value="2">-18°C</option><option value="3">Kamertemperatuur</option></select></div>
        </form>
        <template #footer><button class="button" type="button" @click="emit('update:visible', false)">Annuleren</button><button class="button primary" type="submit" form="sample-buffer-action-form" :disabled="loading"><LoaderCircle v-if="loading" class="spin" :size="16" /><Save v-else :size="16" />Uitvoeren</button></template>
    </AppDialog>
</template>

<style scoped>
.buffer-dialog-form { max-width:none; }
</style>