<script setup>
import { ref } from 'vue';
import { Code2, ListChecks } from '@lucide/vue';
import { dilutionOptions, toObject, toText } from '../dilutions';

const props = defineProps({
    modelValue: { type: String, default: '' },
    problem: { type: String, default: '' },
    inputId: { type: String, default: 'dilution-text' },
    disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);
const mode = ref('picker');

function selected(option) {
    return String(toObject(props.modelValue)[option.key] ?? '') === option.value;
}

function toggle(option, checked) {
    const dilutions = toObject(props.modelValue);
    if (checked) dilutions[option.key] = option.value;
    else delete dilutions[option.key];
    emit('update:modelValue', toText(dilutions));
}
</script>

<template>
    <div class="field wide dilution-control">
        <div class="dilution-heading"><label :for="inputId">Verdunningen</label><div class="dilution-mode" role="tablist" aria-label="Invoermethode verdunningen"><button type="button" role="tab" :aria-selected="mode === 'picker'" title="Interactieve selectie" @click="mode = 'picker'"><ListChecks :size="15" /></button><button type="button" role="tab" :aria-selected="mode === 'text'" title="Tekstinvoer" @click="mode = 'text'"><Code2 :size="15" /></button></div></div>
        <div v-if="mode === 'picker'" class="dilution-picker"><label v-for="option in dilutionOptions" :key="option.key"><input type="checkbox" :checked="selected(option)" :disabled="disabled" @change="toggle(option, $event.target.checked)"><span>{{ option.label }}</span></label></div>
        <textarea v-else :id="inputId" :value="modelValue" rows="6" placeholder="-1=0.1" :disabled="disabled" :aria-invalid="Boolean(problem)" @input="emit('update:modelValue', $event.target.value)"></textarea>
        <small>De interactieve selectie en tekstweergave blijven gesynchroniseerd.</small><span v-if="problem" class="error-text" role="alert">{{ problem }}</span>
    </div>
</template>
