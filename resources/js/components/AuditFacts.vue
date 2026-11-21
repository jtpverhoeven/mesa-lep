<script setup>
defineProps({ rows: { type: Array, required: true } });

function show(value) {
    return value === null || value === undefined || value === '' ? '-' : value;
}
</script>

<template>
    <dl class="audit-facts">
        <template v-for="row in rows" :key="row.label">
            <dt>{{ row.label }}</dt>
            <dd><slot name="value" :row="row">{{ show(row.value) }}</slot></dd>
        </template>
    </dl>
</template>
