<script setup>
import { Check, LoaderCircle, ScanBarcode } from '@lucide/vue';
import ProgressBar from 'openvue/progressbar';
import { GATHER_STEPS } from '../stores/auditTrailStore';

defineProps({ step: { type: Number, required: true }, progress: { type: Number, required: true }, barcode: { type: String, default: '' } });
</script>

<template>
    <section class="audit-loading" role="status" aria-live="polite">
        <div class="audit-scanner" aria-hidden="true">
            <ScanBarcode :size="44" />
            <span class="audit-scanner-line"></span>
        </div>
        <h2>Gegevens verzamelen<span v-if="barcode"> voor {{ barcode }}</span></h2>
        <ProgressBar :value="progress" :show-value="false" class="audit-progress" :aria-label="`Voortgang ${progress}%`" />
        <ol class="audit-steps">
            <li v-for="(label, index) in GATHER_STEPS" :key="label" :class="{ done: index < step, active: index === step }">
                <Check v-if="index < step" :size="14" aria-hidden="true" />
                <LoaderCircle v-else-if="index === step" :size="14" class="animate-spin" aria-hidden="true" />
                <span v-else class="audit-step-dot" aria-hidden="true"></span>
                {{ label }}
            </li>
        </ol>
    </section>
</template>
