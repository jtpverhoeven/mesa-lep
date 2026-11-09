<script setup>
import { ref, useId } from 'vue';
import Textarea from 'openvue/textarea';
import { fillUrl, getJson } from '../senderApi';

const props = defineProps({
    caption: { type: String, required: true },
    selector: { type: Object, required: true },
    defaultLanguage: { type: String, default: 'nl' },
    templateUrl: { type: String, required: true },
    modelValue: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue', 'bcc']);
const uid = useId();
const language = ref(props.defaultLanguage);
const templateId = ref(props.selector.options.find((option) => option.selected)?.id ?? props.selector.options[0]?.id ?? '');
const loading = ref(false);
const error = ref('');

async function load() {
    if (templateId.value === '') return;
    loading.value = true;
    error.value = '';
    try {
        const data = await getJson(fillUrl(props.templateUrl, { __TEMPLATE__: templateId.value, __LANG__: language.value }));
        emit('update:modelValue', data.template ?? '');
        emit('bcc', data.bcc ?? '');
    } catch {
        error.value = 'Het email sjabloon kon niet worden geladen.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <section class="grid gap-3 border-b border-line pb-4 last:border-b-0" :aria-labelledby="`${uid}-caption`">
        <h3 :id="`${uid}-caption`" class="font-semibold">{{ caption }}</h3>
        <p v-if="!selector.options.length" class="report-feedback" role="status">Er zijn nog geen email sjablonen voor dit type ingesteld. U kunt de tekst hieronder handmatig invullen.</p>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div class="field">
                <label :for="`${uid}-template`">Email sjabloon</label>
                <select :id="`${uid}-template`" v-model="templateId" class="form-control" :disabled="loading || !selector.options.length" @change="load">
                    <option v-for="option in selector.options" :key="option.id" :value="option.id">{{ option.name }}</option>
                </select>
            </div>
            <div class="field">
                <label :for="`${uid}-language`">Taal</label>
                <select :id="`${uid}-language`" v-model="language" class="form-control" :disabled="loading" @change="load">
                    <option value="nl">Nederlands</option>
                    <option value="en">Engels</option>
                </select>
            </div>
        </div>
        <p v-if="error" class="report-feedback danger" role="alert">{{ error }}</p>
        <div class="field">
            <label :for="`${uid}-message`">Email (Markdown compatible)</label>
            <Textarea :id="`${uid}-message`" :model-value="modelValue" rows="12" autocomplete="off" fluid @update:model-value="emit('update:modelValue', $event)" />
        </div>
    </section>
</template>
