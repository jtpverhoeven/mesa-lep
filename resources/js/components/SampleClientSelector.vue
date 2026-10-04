<script setup>
import { computed, ref } from 'vue';
import { LoaderCircle, Search, X } from '@lucide/vue';
import AutoComplete from 'openvue/autocomplete';
import { useClientSelectorStore } from '../stores/clientSelectorStore';
import { useCreateSampleStore } from '../stores/createSampleStore';

const props = defineProps({ registrationStore: { type: Object, default: null } });
const clientStore = useClientSelectorStore();
const sampleStore = props.registrationStore ?? useCreateSampleStore();
const searchInput = ref(null);
const autocompleteValue = computed({
    get: () => clientStore.selected ?? clientStore.query,
    set: (value) => {
        if (value && typeof value === 'object') {
            selectClient(value);
            return;
        }

        const query = String(value ?? '');
        if (clientStore.selected && query !== clientStore.selected.name) sampleStore.clearClient();
        clientStore.query = query;

        if (query.trim().length < 2) clientStore.search(query);
    },
});

function complete({ query }) {
    clientStore.search(query);
}

function selectClient(client) {
    if (clientStore.selected?.id !== client.id) sampleStore.selectClient(client);
}

function focus() {
    searchInput.value?.$el?.querySelector('input')?.focus();
}

defineExpose({ focus });
</script>

<template>
    <div class="field wide client-combobox">
        <label for="sample-client">Klant</label>
        <div class="client-search-control">
            <Search :size="16" aria-hidden="true" />
            <AutoComplete
                ref="searchInput"
                v-model="autocompleteValue"
                input-id="sample-client"
                :suggestions="clientStore.results"
                option-label="name"
                data-key="id"
                placeholder="Typ minimaal 2 tekens"
                :delay="350"
                :min-length="2"
                :auto-option-focus="true"
                :complete-on-focus="clientStore.query.trim().length >= 2"
                :loading="clientStore.loading"
                :show-empty-message="!clientStore.loading"
                show-clear
                append-to="self"
                panel-class="client-results"
                unstyled
                @complete="complete"
                @option-select="selectClient($event.value)"
            >
                <template #option="{ option }">
                    <strong>{{ option.name }}</strong>
                    <small v-if="option.reference">{{ option.reference }}</small>
                </template>
                <template #empty>
                    <span v-if="clientStore.error" class="client-results-empty error-text">{{ clientStore.error }}</span>
                    <span v-else class="client-results-empty">Geen klanten gevonden.</span>
                </template>
                <template #loader>
                    <LoaderCircle class="spin client-loader" :size="16" aria-label="Klanten laden" />
                </template>
                <template #clearicon="{ clearCallback }">
                    <button class="client-clear" type="button" title="Klant wissen" aria-label="Klant wissen" @mousedown.prevent @click="clearCallback"><X :size="16" aria-hidden="true" /></button>
                </template>
            </AutoComplete>
        </div>
    </div>
</template>