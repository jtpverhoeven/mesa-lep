<script setup>
import { LoaderCircle } from '@lucide/vue';
import InputText from 'openvue/inputtext';
import Textarea from 'openvue/textarea';

const props = defineProps({
    sampleData: { type: Object, required: true },
    canUpdate: { type: Boolean, required: true },
    readOnly: { type: Boolean, default: false },
    readOnlyMessage: { type: String, default: 'Dit project is vergrendeld of geautoriseerd.' },
    savingFields: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['save']);

function saving(source, field) {
    return Boolean(props.savingFields[`${source}:${field}`]);
}

function save(source, field, value) {
    emit('save', source, field, value);
}

function filterVolume(event, field) {
    field.value = event.target.value.replace(/[^0-9,]/g, '');
}
</script>

<template>
    <form class="project-sample-fields" @submit.prevent>
        <label>
            <span>Bemonster. methode</span>
            <span class="project-sample-control">
                <select v-model="sampleData.sample.sampling_method" class="form-control min-h-8 px-2 py-1.5" :disabled="!canUpdate || readOnly || saving('attribute', 'sampling_method')" @change="save('attribute', 'sampling_method', $event.target.value)">
                    <option v-for="method in sampleData.sampling_methods" :key="method.id" :value="method.id">{{ method.name }}</option>
                </select>
                <LoaderCircle v-if="saving('attribute', 'sampling_method')" class="spin" :size="14" />
            </span>
        </label>
        <label>
            <span>Monster omschrijving LIMS</span>
            <span class="project-sample-control">
                <InputText v-model="sampleData.sample.description" size="small" fluid :disabled="!canUpdate || readOnly || saving('attribute', 'description')" @change="save('attribute', 'description', $event.target.value)" />
                <LoaderCircle v-if="saving('attribute', 'description')" class="spin" :size="14" />
            </span>
        </label>
        <label>
            <span>Volgnummer</span>
            <InputText :model-value="String(sampleData.project_follow_number)" size="small" fluid disabled />
        </label>
        <label>
            <span>Monster omschrijving klant</span>
            <InputText :model-value="sampleData.sample.client_description" size="small" fluid readonly />
        </label>
        <label v-for="field in sampleData.sample_fields" :key="field.name">
            <span>{{ field.label }}</span>
            <span class="project-sample-control">
                <Textarea v-if="field.type === 'textarea'" v-model="field.value" size="small" fluid rows="3" :disabled="!canUpdate || readOnly || saving('custom', field.name)" @change="save('custom', field.name, field.value)" />
                <InputText v-else v-model="field.value" size="small" fluid :disabled="!canUpdate || readOnly || saving('custom', field.name)" @change="save('custom', field.name, field.value)" />
                <LoaderCircle v-if="saving('custom', field.name)" class="spin" :size="14" />
            </span>
        </label>
        <label v-for="field in sampleData.sample_extra" :key="field.name">
            <span>{{ field.label }}</span>
            <span class="project-sample-control">
                <select v-if="field.name === 'type'" v-model="field.value" class="form-control min-h-8 px-2 py-1.5" :disabled="!canUpdate || readOnly || saving('extra', field.name)" @change="save('extra', field.name, field.value)">
                    <option value=""></option><option value="Koud">Koud</option><option value="Warm">Warm</option><option value="Mengwater">Mengwater</option>
                </select>
                <InputText v-else v-model="field.value" size="small" fluid :inputmode="field.name === 'filter_volume' ? 'decimal' : 'text'" :disabled="!canUpdate || readOnly || saving('extra', field.name)" @input="field.name === 'filter_volume' && filterVolume($event, field)" @change="save('extra', field.name, field.value)" />
                <LoaderCircle v-if="saving('extra', field.name)" class="spin" :size="14" />
            </span>
        </label>
        <p v-if="!canUpdate || readOnly" class="project-sample-read-only">{{ canUpdate ? readOnlyMessage : 'U heeft alleen-lezen toegang tot monsterdetails.' }}</p>
    </form>
</template>

<style scoped>
.project-sample-fields { display:grid; gap:10px; }
.project-sample-fields > label { display:grid; grid-template-columns:minmax(105px,.85fr) minmax(0,1.4fr); align-items:center; gap:9px; font-size:11px; }
.project-sample-fields > label > span:first-child { color:var(--muted); overflow-wrap:anywhere; }
.project-sample-fields input,.project-sample-fields select,.project-sample-fields textarea { width:100%; min-width:0; }
.project-sample-fields textarea { resize:vertical; }
.project-sample-control { position:relative; display:flex; align-items:center; min-width:0; }
.project-sample-control > svg { position:absolute; right:8px; color:var(--accent); pointer-events:none; }
.project-sample-read-only { margin:2px 0 0; padding:8px; background:#fff3cf; color:#714d00; font-size:11px; }
@media(max-width:420px) { .project-sample-fields > label { grid-template-columns:minmax(0,1fr); gap:4px; } }
</style>