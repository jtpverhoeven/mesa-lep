<script setup>
import { ref } from 'vue';

const props = defineProps({
    assayField: { type: Object, default: () => ({}) },
    action: { type: String, required: true },
    method: { type: String, default: 'POST' },
    submitLabel: { type: String, required: true },
    cancelUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});

function isDefaultValueEnabled(value, fallback) {
    if (value === undefined || value === null || value === '') {
        return fallback;
    }

    return value === true || value === 1 || value === '1';
}

const form = ref({
    name: props.assayField.name ?? '',
    hasDefaultValue: isDefaultValueEnabled(
        props.assayField.has_default_value,
        props.assayField.standard_value !== null && props.assayField.standard_value !== undefined,
    ),
    standardValue: props.assayField.standard_value ?? '',
    position: props.assayField.position ?? 0,
});
</script>

<template>
    <form :method="method === 'GET' ? 'GET' : 'POST'" :action="action">
        <input type="hidden" name="_token" :value="csrf">
        <input v-if="method !== 'POST' && method !== 'GET'" type="hidden" name="_method" :value="method">
        <fieldset class="form-section"><legend>Analyseveld</legend><div class="form-grid">
            <div class="field"><label for="name">Veldnaam</label><input id="name" v-model="form.name" name="name" maxlength="64" required autofocus></div>
            <div class="field wide"><input type="hidden" name="has_default_value" value="0"><label><input v-model="form.hasDefaultValue" type="checkbox" id="has_default_value" name="has_default_value" value="1"> Dit veld heeft een standaardwaarde</label></div>
            <div class="field" :hidden="!form.hasDefaultValue"><label for="standard_value">Standaardwaarde</label><input id="standard_value" v-model="form.standardValue" name="standard_value" maxlength="64"></div>
            <div class="field"><label for="position">Positie</label><input id="position" v-model="form.position" type="number" name="position" min="0" required></div>
        </div></fieldset>
        <div class="form-actions"><button class="button primary" type="submit">{{ submitLabel }}</button><a class="button" :href="cancelUrl">Annuleren</a></div>
    </form>
</template>