<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { LoaderCircle, Search, X } from '@lucide/vue';
import { useClientSelectorStore } from '../stores/clientSelectorStore';
import { useCreateSampleStore } from '../stores/createSampleStore';

const clientStore = useClientSelectorStore();
const sampleStore = useCreateSampleStore();
const open = ref(false);
const activeIndex = ref(-1);
let searchTimer;

const activeResultId = computed(() => activeIndex.value >= 0 ? `client-result-${clientStore.results[activeIndex.value]?.id}` : undefined);

function inputChanged(event) {
    const query = event.target.value;
    if (clientStore.selected && query !== clientStore.selected.name) sampleStore.clearClient();
    clientStore.query = query;
    open.value = true;
    activeIndex.value = -1;
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => clientStore.search(query), 350);
}

function select(client) {
    open.value = false;
    activeIndex.value = -1;
    sampleStore.selectClient(client);
}

function move(direction) {
    if (!clientStore.results.length) return;
    open.value = true;
    activeIndex.value = (activeIndex.value + direction + clientStore.results.length) % clientStore.results.length;
}

function selectActive() {
    if (activeIndex.value >= 0) select(clientStore.results[activeIndex.value]);
}

onBeforeUnmount(() => window.clearTimeout(searchTimer));
</script>

<template>
    <div class="field wide client-combobox">
        <label for="sample-client">Klant</label>
        <div class="client-search-control">
            <Search :size="16" aria-hidden="true" />
            <input id="sample-client" :value="clientStore.query" type="search" autocomplete="off" placeholder="Typ minimaal 2 tekens" role="combobox" aria-autocomplete="list" aria-controls="client-results" :aria-expanded="open && clientStore.query.trim().length >= 2" :aria-activedescendant="activeResultId" autofocus @input="inputChanged" @focus="open = true" @blur="open = false" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.enter.prevent="selectActive" @keydown.esc="open = false">
            <LoaderCircle v-if="clientStore.loading" class="spin" :size="16" aria-label="Klanten laden" />
            <button v-else-if="clientStore.query" class="client-clear" type="button" title="Klant wissen" aria-label="Klant wissen" @mousedown.prevent="sampleStore.clearClient"><X :size="16" aria-hidden="true" /></button>
        </div>
        <div v-if="open && clientStore.query.trim().length >= 2" id="client-results" class="client-results" role="listbox">
            <button v-for="(client, index) in clientStore.results" :id="`client-result-${client.id}`" :key="client.id" type="button" role="option" :aria-selected="activeIndex === index" :class="{ active: activeIndex === index }" @mousedown.prevent="select(client)" @mouseenter="activeIndex = index"><strong>{{ client.name }}</strong><small v-if="client.reference">{{ client.reference }}</small></button>
            <p v-if="!clientStore.loading && !clientStore.results.length && !clientStore.error" class="client-results-empty">Geen klanten gevonden.</p>
            <p v-if="clientStore.error" class="client-results-empty error-text">{{ clientStore.error }}</p>
        </div>
        <p v-if="clientStore.selected" class="selected-client">Geselecteerd: <strong>{{ clientStore.selected.name }}</strong></p>
    </div>
</template>