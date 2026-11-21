<script setup>
defineProps({ title: { type: String, required: true }, icon: { type: [Object, Function], default: null } });
</script>

<template>
    <section class="sample-panel audit-section">
        <h2>
            <component :is="icon" v-if="icon" :size="16" aria-hidden="true" />{{ title }}
            <span class="ml-auto flex flex-wrap items-center gap-1.5 font-normal"><slot name="tools" /></span>
        </h2>
        <div class="sample-panel-body"><slot /></div>
    </section>
</template>
