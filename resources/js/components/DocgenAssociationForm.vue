<script setup>
import { ref } from 'vue';
import Button from 'openvue/button';
import AssayAssociationSelector from './AssayAssociationSelector.vue';

const props = defineProps({
    assays: { type: Array, default: () => [] },
    selectedAssayIds: { type: Array, default: () => [] },
    templates: { type: Array, default: () => [] },
    showHidden: Boolean,
    formAction: { type: String, required: true },
    copyAction: { type: String, required: true },
    toggleHiddenUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    sourceTemplate: { type: [String, Number], default: '' },
});
const selectedAssayIds = ref(props.selectedAssayIds.map(Number));
const sourceTemplate = ref(props.sourceTemplate);

function toggleAssay(id, checked) {
    const selected = new Set(selectedAssayIds.value);
    checked ? selected.add(id) : selected.delete(id);
    selectedAssayIds.value = [...selected];
}

function toggleHidden() {
    window.location.assign(props.toggleHiddenUrl);
}

function confirmCopy(event) {
    if (!window.confirm('Alle huidige associaties vervangen door die van het geselecteerde sjabloon?')) {
        event.preventDefault();
    }
}
</script>

<template>
    <div class="assay-association-editor">
        <form class="assay-association-panel" :action="formAction" method="post">
            <input type="hidden" name="_token" :value="csrf">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="show_hidden" :value="Number(showHidden)">

            <div class="assay-association-heading">
                <div><h2>Analyses op dit sjabloon</h2><p>Selecteer de analyses die met dit rapportsjabloon kunnen worden gerapporteerd.</p></div>
                <div class="assay-association-filters">
                    <label><input type="checkbox" :checked="showHidden" @change="toggleHidden"> Toon ook inactieve analyses</label>
                </div>
            </div>

            <AssayAssociationSelector :assays="assays" :selected-assay-ids="selectedAssayIds" input-name="assay_ids[]" @toggle="toggleAssay" />

            <div class="form-actions"><Button type="submit" label="Associaties opslaan" /></div>
        </form>

        <form class="assay-association-panel" :action="copyAction" method="post" @submit="confirmCopy">
            <input type="hidden" name="_token" :value="csrf">
            <fieldset class="form-section">
                <legend>Associaties kopieren</legend>
                <div class="field">
                    <label for="source-template">Overnemen van</label>
                    <select id="source-template" v-model="sourceTemplate" name="source_template" required>
                        <option value="">Selecteer een sjabloon</option>
                        <option v-for="template in templates" :key="template.id" :value="template.id">{{ template.name }}</option>
                    </select>
                </div>
            </fieldset>
            <div class="form-actions"><Button type="submit" label="Associaties vervangen" severity="secondary" /></div>
        </form>
    </div>
</template>
