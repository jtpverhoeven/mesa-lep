<script setup>
import { Search } from '@lucide/vue';
</script>

<template>
    <div class="research-tabs" role="tablist" aria-label="Onderzoekstype"><button type="button" role="tab" aria-selected="false" disabled>Profielen</button><button type="button" role="tab" aria-selected="true">Analyses</button></div>
    <div class="field wide"><label for="assay-matrix">Matrix</label><select id="assay-matrix" disabled><option>Selecteer matrix</option></select></div>
    <div class="assay-filters"><label><input type="checkbox" disabled> Geaccrediteerd</label><label><input type="checkbox" disabled> Niet geaccrediteerd</label></div>
    <label class="assay-search"><Search :size="16" aria-hidden="true" /><span class="sr-only">Zoeken in matrix</span><input type="search" placeholder="Zoeken in matrix" disabled></label>
    <div class="assay-placeholder-list"><p>Geen analyses geselecteerd.</p></div>
</template>