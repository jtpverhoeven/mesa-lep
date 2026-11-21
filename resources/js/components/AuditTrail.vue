<script setup>
import { onMounted, ref } from 'vue';
import { Barcode, Printer, Search } from '@lucide/vue';
import Button from 'openvue/button';
import IconField from 'openvue/iconfield';
import InputIcon from 'openvue/inputicon';
import InputText from 'openvue/inputtext';
import { sfx } from '../sfx.js';
import { useAuditTrailStore } from '../stores/auditTrailStore';
import AuditTrailLoading from './AuditTrailLoading.vue';
import AuditTrailReport from './AuditTrailReport.vue';

const props = defineProps({
    endpoints: { type: Object, required: true },
    links: { type: Object, required: true },
    initialBarcode: { type: String, default: '' },
});
const store = useAuditTrailStore();
const barcodeInput = ref(null);
store.endpoints = props.endpoints;

async function scan() {
    const loaded = await store.lookup();

    if (loaded !== null) void sfx.play(loaded ? 'do' : 'warning').catch(() => {});
    const input = barcodeInput.value?.$el;
    input?.focus();
    input?.select();
}

onMounted(() => {
    if (props.initialBarcode) {
        store.barcode = props.initialBarcode;
        scan();
    }
});
</script>

<template>
    <div class="audit-trail" :aria-busy="store.loading">
        <form class="audit-scan-form" @submit.prevent="scan">
            <label class="sr-only" for="audit-trail-barcode">Barcode</label>
            <IconField class="audit-scan-field">
                <InputIcon><Barcode :size="16" aria-hidden="true" /></InputIcon>
                <InputText id="audit-trail-barcode" ref="barcodeInput" v-model="store.barcode" autofocus autocomplete="off" placeholder="Scan of typ een barcode" maxlength="32" :disabled="store.loading" fluid @focus="$event.target.select()" />
            </IconField>
            <Button type="submit" title="Audit trail ophalen" :disabled="store.loading || !store.barcode.trim()"><Search :size="16" />Ophalen</Button>
            <Button v-if="store.data" as="a" severity="secondary" :href="store.printUrl()" target="_blank" rel="noopener" title="Afdrukweergave in een nieuw tabblad"><Printer :size="16" />Afdrukweergave</Button>
        </form>
        <p v-if="store.error" class="notice error" role="alert">{{ store.error }}</p>
        <AuditTrailLoading v-if="store.loading" :step="store.step" :progress="store.progress" :barcode="store.barcode" />
        <AuditTrailReport v-else-if="store.data" :trail="store.data" :links="links" />
        <p v-else-if="!store.error" class="empty-state">Scan een monsternummer om de audit trail van het monster en het bijbehorende project te bekijken.</p>
    </div>
</template>
