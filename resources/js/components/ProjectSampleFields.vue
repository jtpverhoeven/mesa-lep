<script setup>
import { computed } from 'vue';
import { LoaderCircle } from '@lucide/vue';
import { useProjectSearchStore } from '../stores/projectSearchStore';

const props = defineProps({ canUpdate: { type: Boolean, required: true } });
const store = useProjectSearchStore();
const readOnly = computed(() => !props.canUpdate || store.sampleReadOnly);

function saving(source, field) {
    return Boolean(store.savingSample[`${source}:${field}`]);
}

function save(source, field, value) {
    return store.updateSampleField(source, field, value);
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
                <select v-model="store.sampleData.sample.sampling_method" :disabled="readOnly || saving('attribute', 'sampling_method')" @change="save('attribute', 'sampling_method', $event.target.value)">
                    <option v-for="method in store.sampleData.sampling_methods" :key="method.id" :value="method.id">{{ method.name }}</option>
                </select>
                <LoaderCircle v-if="saving('attribute', 'sampling_method')" class="spin" :size="14" />
            </span>
        </label>
        <label>
            <span>Monster omschrijving LIMS</span>
            <span class="project-sample-control">
                <input v-model="store.sampleData.sample.description" type="text" :disabled="readOnly || saving('attribute', 'description')" @change="save('attribute', 'description', $event.target.value)">
                <LoaderCircle v-if="saving('attribute', 'description')" class="spin" :size="14" />
            </span>
        </label>
        <label>
            <span>Volgnummer</span>
            <input :value="store.sampleData.project_follow_number" type="text" disabled>
        </label>
        <label>
            <span>Monster omschrijving klant</span>
            <input :value="store.sampleData.sample.client_description" type="text" readonly>
        </label>
        <label v-for="field in store.sampleData.sample_fields" :key="field.name">
            <span>{{ field.label }}</span>
            <span class="project-sample-control">
                <textarea v-if="field.type === 'textarea'" v-model="field.value" rows="3" :disabled="readOnly || saving('custom', field.name)" @change="save('custom', field.name, field.value)"></textarea>
                <input v-else v-model="field.value" type="text" :disabled="readOnly || saving('custom', field.name)" @change="save('custom', field.name, field.value)">
                <LoaderCircle v-if="saving('custom', field.name)" class="spin" :size="14" />
            </span>
        </label>
        <label v-for="field in store.sampleData.sample_extra" :key="field.name">
            <span>{{ field.label }}</span>
            <span class="project-sample-control">
                <select v-if="field.name === 'type'" v-model="field.value" :disabled="readOnly || saving('extra', field.name)" @change="save('extra', field.name, field.value)">
                    <option value=""></option><option value="Koud">Koud</option><option value="Warm">Warm</option><option value="Mengwater">Mengwater</option>
                </select>
                <input v-else v-model="field.value" type="text" :inputmode="field.name === 'filter_volume' ? 'decimal' : 'text'" :disabled="readOnly || saving('extra', field.name)" @input="field.name === 'filter_volume' && filterVolume($event, field)" @change="save('extra', field.name, field.value)">
                <LoaderCircle v-if="saving('extra', field.name)" class="spin" :size="14" />
            </span>
        </label>
        <p v-if="readOnly" class="project-sample-read-only">{{ canUpdate ? 'Dit project is vergrendeld of geautoriseerd.' : 'U heeft alleen-lezen toegang tot monsterdetails.' }}</p>
    </form>
</template>

<style scoped>
.project-sample-fields { display:grid; gap:10px; }
.project-sample-fields > label { display:grid; grid-template-columns:minmax(105px,.85fr) minmax(0,1.4fr); align-items:center; gap:9px; font-size:11px; }
.project-sample-fields > label > span:first-child { color:var(--muted); overflow-wrap:anywhere; }
.project-sample-fields input,.project-sample-fields select,.project-sample-fields textarea { width:100%; min-width:0; min-height:32px; padding:6px 8px; border:1px solid #9eabb2; border-radius:2px; background:var(--surface); color:var(--ink); font:inherit; }
.project-sample-fields textarea { resize:vertical; }
.project-sample-fields input:disabled,.project-sample-fields select:disabled,.project-sample-fields textarea:disabled,.project-sample-fields input[readonly] { border-color:var(--line); background:var(--surface-alt); color:var(--muted); opacity:1; }
.project-sample-control { position:relative; display:flex; align-items:center; min-width:0; }
.project-sample-control > svg { position:absolute; right:8px; color:var(--accent); pointer-events:none; }
.project-sample-read-only { margin:2px 0 0; padding:8px; background:#fff3cf; color:#714d00; font-size:11px; }
@media(max-width:420px) { .project-sample-fields > label { grid-template-columns:minmax(0,1fr); gap:4px; } }
</style>