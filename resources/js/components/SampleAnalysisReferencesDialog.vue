<script setup>
import { computed } from 'vue';
import { LoaderCircle } from '@lucide/vue';
import Button from 'openvue/button';
import InputText from 'openvue/inputtext';
import Select from 'openvue/select';
import AppDialog from './AppDialog.vue';
import { useSampleLookupStore } from '../stores/sampleLookupStore';
import { referenceFields, referenceValues } from '../researchProfileAssays';

const store = useSampleLookupStore();
const fields = computed(() => referenceFields(store.referenceDraft));
const sourceRequired = computed(() => Boolean(store.referenceDraft && Object.keys(referenceValues(store.referenceDraft)).length));
</script>

<template>
    <AppDialog :visible="store.referencesVisible" title="Referentiewaarden wijzigen" width="600px" :dismissable-mask="false" :closable="!store.saving" :close-on-escape="!store.saving" @update:visible="store.closeReferences()">
        <div v-if="store.referencesLoading" class="flex items-center gap-2 text-muted" role="status"><LoaderCircle class="spin" :size="18" />Referentiewaarden laden...</div>
        <form v-else-if="store.referenceDraft" id="sample-analysis-references" class="grid min-w-0 gap-4" @submit.prevent="store.saveReferences()">
            <strong>{{ store.referenceDraft.name }}</strong>
            <p class="m-0 text-muted">Laat leeg als er geen referentie geldt. Bij een ingevulde waarde, ook 0, is een referentiebron verplicht.</p>
            <p v-if="!store.referenceDraft.configuration?.reference_outputs" class="m-0 text-danger" role="alert">De uitvoervelden van deze workflow zijn nog niet geconfigureerd. Bestaande referenties blijven beschikbaar; nieuwe referentievelden kunnen nog niet worden toegevoegd.</p>
            <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                <div v-for="(field, index) in fields" :key="field.key" class="field">
                    <label :for="`sample-reference-${index}`">{{ field.label }}</label>
                    <InputText :id="`sample-reference-${index}`" v-model="store.referenceDraft.reference[field.key]" placeholder="Geen referentie" :disabled="store.saving || store.readOnly" />
                    <small v-if="field.label !== field.name" class="text-muted">{{ field.name }}</small>
                </div>
            </div>
            <div class="field">
                <label id="sample-reference-source-label" for="sample-reference-source">Referentiebron <span v-if="sourceRequired" class="text-danger">(verplicht)</span></label>
                <Select v-model="store.referenceDraft.reference_source" input-id="sample-reference-source" aria-labelledby="sample-reference-source-label" :options="store.referenceSources" option-label="name" option-value="id" filter show-clear placeholder="Geen bron" :disabled="store.saving || store.readOnly" :invalid="Boolean(store.referenceProblem)" :aria-required="sourceRequired" aria-describedby="sample-reference-source-help" />
                <small id="sample-reference-source-help" :class="store.referenceProblem ? 'text-danger' : 'text-muted'" :role="store.referenceProblem ? 'alert' : undefined">{{ store.referenceProblem || 'Alleen globale bronnen en bronnen van de klant van dit monster zijn beschikbaar.' }}</small>
            </div>
        </form>
        <p v-if="store.referencesError" class="mb-0 text-danger" role="alert">{{ store.referencesError }}</p>
        <template #footer>
            <Button type="button" label="Referentie verwijderen" severity="danger" variant="outlined" :disabled="!store.referenceDraft || store.referencesLoading || store.saving || store.readOnly" @click="store.saveReferences(true)" />
            <Button type="button" label="Annuleren" severity="secondary" :disabled="store.saving" @click="store.closeReferences()" />
            <Button type="submit" form="sample-analysis-references" label="Opslaan" :loading="store.saving" :disabled="!store.referenceDraft || store.referencesLoading || store.saving || store.readOnly || Boolean(store.referenceProblem)" />
        </template>
    </AppDialog>
</template>
