<script setup>
import { onMounted } from 'vue';
import { Printer } from '@lucide/vue';
import Button from 'openvue/button';
import AuditTrailReport from './AuditTrailReport.vue';

defineProps({ trail: { type: Object, required: true }, links: { type: Object, required: true } });

const print = () => window.print();

onMounted(() => window.setTimeout(print, 400));
</script>

<template>
    <div class="audit-trail audit-printing">
        <header class="audit-print-header">
            <h1>Audit trail {{ trail.barcode }}</h1>
            <Button class="print:hidden" type="button" @click="print"><Printer :size="16" />Afdrukken</Button>
        </header>
        <AuditTrailReport :trail="trail" :links="links" />
    </div>
</template>
