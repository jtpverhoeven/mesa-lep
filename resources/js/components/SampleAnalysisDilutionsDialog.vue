<script setup>
import { LoaderCircle } from '@lucide/vue';
import Button from 'openvue/button';
import AppDialog from './AppDialog.vue';
import DilutionSelector from './DilutionSelector.vue';
import { useSampleLookupStore } from '../stores/sampleLookupStore';

const store = useSampleLookupStore();
</script>

<template>
    <AppDialog :visible="store.dilutionsVisible" title="Verdunningen wijzigen" width="620px" :dismissable-mask="false" :closable="!store.saving" :close-on-escape="!store.saving" @update:visible="store.closeDilutions()">
        <div v-if="store.dilutionsLoading" class="flex items-center gap-2 text-muted" role="status"><LoaderCircle class="spin" :size="18" />Verdunningen laden...</div>
        <form v-else-if="store.dilutionDraft" id="sample-analysis-dilutions" class="form-grid" @submit.prevent="store.saveDilutions()">
            <strong class="wide">{{ store.dilutionDraft.name }}</strong>
            <p class="wide m-0 text-muted">Ingevoerde resultaten blijven behouden voor verdunningen en replica's die overblijven. Het resultaat wordt opnieuw berekend.</p>
            <p v-if="!store.dilutionDraft.usesDilution && !store.dilutionDraft.usesReplicates" class="wide m-0 text-danger" role="alert">Voor deze analyse kunnen geen verdunningen of replica's worden gewijzigd.</p>
            <DilutionSelector v-if="store.dilutionDraft.usesDilution" v-model="store.dilutionDraft.text" :problem="store.dilutionProblem" input-id="sample-dilutions-text" :disabled="store.saving" />
            <div v-if="store.dilutionDraft.usesReplicates" class="field">
                <label for="sample-dilutions-replicates">Replica's</label>
                <input id="sample-dilutions-replicates" v-model.number="store.dilutionDraft.replicates" type="number" min="0" max="99" :disabled="store.saving" @input="store.dilutionDraft.replicatesTouched = true">
            </div>
        </form>
        <p v-if="store.dilutionsError" class="mb-0 text-danger" role="alert">{{ store.dilutionsError }}</p>
        <template #footer>
            <Button type="button" label="Annuleren" severity="secondary" :disabled="store.saving" @click="store.closeDilutions()" />
            <Button type="submit" form="sample-analysis-dilutions" label="Opslaan" :loading="store.saving" :disabled="!store.dilutionDraft || store.dilutionsLoading || store.saving || store.readOnly || Boolean(store.dilutionProblem) || (!store.dilutionDraft.usesDilution && !store.dilutionDraft.usesReplicates)" />
        </template>
    </AppDialog>
</template>
