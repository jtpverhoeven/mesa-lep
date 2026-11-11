<script setup>
import { ref } from 'vue';
import { Building2, FileSpreadsheet, Info, ListChecks, LoaderCircle, Save, TriangleAlert, UploadCloud } from '@lucide/vue';
import SampleBufferImportRows from './SampleBufferImportRows.vue';
import SampleClientSelector from './SampleClientSelector.vue';
import { useSampleBufferImportStore } from '../stores/sampleBufferImportStore';

const props = defineProps({ endpoints: { type: Object, required: true } });
const store = useSampleBufferImportStore();
const dragging = ref(false);

store.configure(props.endpoints);

function chooseFile(event) {
    store.setFile(event.target.files[0] ?? null);
}

function dropFile(event) {
    dragging.value = false;
    const file = event.dataTransfer.files[0];
    if (file) store.setFile(file);
}
</script>

<template>
    <div class="import-layout">
        <div v-if="store.result" class="notice success" role="status">
            <strong>{{ store.result.count }} {{ store.result.count === 1 ? 'monster is' : 'monsters zijn' }} toegevoegd aan het voorportaal.</strong>
            <a v-if="store.result.portal_url" class="text-link" :href="store.result.portal_url">Open het voorportaal</a>
        </div>
        <div v-if="store.error" class="notice error" role="alert">
            <strong>{{ store.error }}</strong>
            <ul v-if="store.commitErrors.length"><li v-for="message in store.commitErrors" :key="message">{{ message }}</li></ul>
        </div>

        <div class="import-setup">
            <section class="sample-panel">
                <h2><Building2 :size="16" aria-hidden="true" />Klant</h2>
                <div class="sample-panel-body form-grid">
                    <SampleClientSelector :registration-store="store" />
                </div>
            </section>
            <section class="sample-panel">
                <h2><FileSpreadsheet :size="16" aria-hidden="true" />CSV-bestand</h2>
                <div class="sample-panel-body form-grid">
                    <p class="panel-hint wide">
                        <a class="text-link" :href="endpoints.template" download>Download het bulkformulier</a>, sla het 3e tabblad op als CSV (UTF-8) en kies dat bestand hier.
                    </p>
                    <label
                        for="import-file"
                        class="wide grid min-h-28 place-items-center rounded-control border-2 border-dashed p-4 text-center transition-colors cursor-pointer"
                        :class="dragging ? 'border-accent bg-accent-faint' : 'border-control-border bg-surface hover:bg-surface-alt'"
                        @dragenter.prevent="dragging = true"
                        @dragover.prevent="dragging = true"
                        @dragleave.prevent="dragging = false"
                        @drop.prevent="dropFile"
                    >
                        <input id="import-file" :key="store.fileInputKey" class="sr-only" type="file" accept=".csv,.txt,text/csv,text/plain" @click="$event.target.value = ''" @change="chooseFile">
                        <span class="pointer-events-none grid justify-items-center gap-1.5">
                            <LoaderCircle v-if="store.previewing" class="spin text-accent" :size="26" aria-hidden="true" />
                            <UploadCloud v-else class="text-accent" :size="26" aria-hidden="true" />
                            <strong>{{ store.file ? store.file.name : 'Sleep een CSV-bestand hierheen' }}</strong>
                            <span class="text-xs text-muted">{{ store.file ? 'Klik om een ander bestand te kiezen.' : 'Of klik om een bestand te kiezen (maximaal 2 MB).' }}</span>
                        </span>
                    </label>
                    <p v-if="store.file && !store.client" class="panel-hint wide" role="status">Kies een klant om het bestand te controleren.</p>
                    <div class="field wide">
                        <label for="import-separator">Scheidingsteken</label>
                        <select id="import-separator" :value="store.separator" @change="store.setSeparator($event.target.value)">
                            <option value="auto">Automatisch herkennen{{ store.separator === 'auto' && store.preview ? ` (${store.separatorLabel})` : '' }}</option>
                            <option value="semicolon">Puntkomma (;)</option>
                            <option value="comma">Komma (,)</option>
                            <option value="tab">Tab</option>
                            <option value="pipe">Verticale streep (|)</option>
                        </select>
                    </div>
                </div>
            </section>
        </div>

        <section v-if="store.previewing || store.preview" class="sample-panel import-review">
            <h2><ListChecks :size="16" aria-hidden="true" />{{ store.preview ? `${store.rows.length} ${store.rows.length === 1 ? 'rij' : 'rijen'} controleren` : 'Rijen controleren' }}</h2>
            <div v-if="!store.preview" class="empty-state" role="status"><LoaderCircle class="spin" :size="18" aria-hidden="true" /> Bestand inlezen</div>
            <template v-else>
                <div class="sample-panel-body grid gap-2.5">
                    <p class="panel-hint">{{ store.file.name }} · {{ store.preview.encoding }} · scheidingsteken: {{ store.separatorLabel }}</p>
                    <div v-if="store.preview.uses_assay_column" class="status-notice" role="status">
                        <Info :size="16" aria-hidden="true" />
                        <div class="status-notice-text">
                            <strong>Productgroep via de kolom Analyses</strong>
                            <span>Voor deze klant bepaalt de tekst in de kolom Analyses de productgroep. De kolom _ProductGroep wordt genegeerd.</span>
                        </div>
                    </div>
                    <div v-if="store.alertCount" class="notice error" role="alert">
                        <TriangleAlert :size="16" aria-hidden="true" />Kon voor {{ store.alertCount }} {{ store.alertCount === 1 ? 'rij' : 'rijen' }} geen passende productgroep vinden voor deze klant. Controleer de gemarkeerde keuzes.
                    </div>
                    <div v-if="store.preview.warnings.length" class="status-notice" role="status">
                        <Info :size="16" aria-hidden="true" />
                        <ul class="status-notice-text"><li v-for="warning in store.preview.warnings" :key="warning">{{ warning }}</li></ul>
                    </div>
                </div>
                <SampleBufferImportRows :rows="store.rows" :meta-columns="store.metaColumns" :product-groups="store.preview.product_groups" :issues="store.issues" @remove="store.removeRow" />
                <div class="form-actions sample-panel-body items-center">
                    <button class="button primary" type="button" :disabled="!store.canCommit" @click="store.submit">
                        <LoaderCircle v-if="store.submitting" class="spin" :size="16" aria-hidden="true" /><Save v-else :size="16" aria-hidden="true" />Importeren naar voorportaal
                    </button>
                    <span v-if="store.issueCount" class="error-text" role="status">{{ store.issueCount }} {{ store.issueCount === 1 ? 'rij bevat' : 'rijen bevatten' }} nog fouten.</span>
                    <span v-else-if="!store.rows.length" class="panel-hint" role="status">Er zijn geen rijen meer om te importeren.</span>
                </div>
            </template>
        </section>
    </div>
</template>
