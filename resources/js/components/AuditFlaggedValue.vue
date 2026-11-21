<script setup>
import { TriangleAlert } from '@lucide/vue';
import Tag from 'openvue/tag';

// An assurance form value as the form itself shows it: red when out of specification or used after its THT date, with the explanation.
defineProps({
    value: { type: Object, required: true },
    material: { type: Boolean, default: false },
    unit: { type: String, default: '' },
});
</script>

<template>
    <span class="audit-value" :class="{ 'audit-flagged': value.flagged }">
        {{ value.value || '-' }}<template v-if="unit && value.value"> {{ unit }}</template>
        <span v-if="value.flagged" class="audit-tags">
            <Tag v-if="value.out_of_specification" severity="danger" class="audit-tag"><TriangleAlert :size="11" aria-hidden="true" />{{ material ? 'Buiten specificatie' : 'THT verlopen' }}</Tag>
            <Tag v-if="value.used_after_expiry" severity="danger" class="audit-tag"><TriangleAlert :size="11" aria-hidden="true" />Gebruikt na THT</Tag>
        </span>
    </span>
    <small v-if="value.flagged && value.note" class="audit-note">Uitleg: {{ value.note }}</small>
</template>
