<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { CircleAlert, LoaderCircle, Search, X } from '@lucide/vue';
import AnimateOnScroll from 'openvue/animateonscroll';
import AutoComplete from 'openvue/autocomplete';
import Button from 'openvue/button';
import DOMPurify from 'dompurify';
import AppDialog from './AppDialog.vue';
import { useSfx } from '../sfx';
import { useClientSelectorStore } from '../stores/clientSelectorStore';
import { useCreateSampleStore } from '../stores/createSampleStore';

const props = defineProps({
    registrationStore: { type: Object, default: null },
    showDetails: { type: Boolean, default: false },
});
const vAnimateOnScroll = AnimateOnScroll;
const clientStore = useClientSelectorStore();
const sampleStore = props.registrationStore ?? useCreateSampleStore();
const sfx = useSfx();
const searchInput = ref(null);
const particularsVisible = ref(false);
const notes = computed(() => DOMPurify.sanitize(clientStore.details?.notes ?? ''));
const hasParticulars = computed(() => Boolean(clientStore.details?.notes || clientStore.details?.files?.length));

watch([() => props.showDetails, () => clientStore.selected?.id], ([enabled, clientId]) => {
    clientStore.loadDetails(enabled ? clientId : null);
}, { immediate: true });

watch([() => props.showDetails, () => clientStore.details], ([enabled]) => {
    particularsVisible.value = false;
    if (enabled && hasParticulars.value) {
        sfx.play('clientwishes').catch((error) => console.warn('Klantbijzonderhedengeluid kon niet worden afgespeeld.', error));
    }
}, { immediate: true });

onBeforeUnmount(() => clientStore.clearDetails());
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
    <template v-if="showDetails">
        <p v-if="clientStore.loadingDetails" class="wide text-muted" role="status">Klantgegevens laden...</p>
        <p v-if="clientStore.detailsError" class="wide error-text" role="alert">{{ clientStore.detailsError }}</p>
        <dl v-if="clientStore.details" class="client-info wide">
            <template v-if="clientStore.details.reference"><dt>Referentie</dt><dd>{{ clientStore.details.reference }}</dd></template>
            <template v-if="clientStore.details.address"><dt>Adres</dt><dd>{{ clientStore.details.address }}</dd></template>
            <template v-if="clientStore.details.telephone"><dt>Telefoon</dt><dd>{{ clientStore.details.telephone }}</dd></template>
        </dl>
        <Button
            v-if="hasParticulars"
            :key="clientStore.selected.id"
            v-animate-on-scroll.once="{ enterClass: 'motion-safe:animate-pulse motion-safe:[animation-iteration-count:2]' }"
            class="wide"
            type="button"
            severity="warn"
            variant="outlined"
            @click="particularsVisible = true"
        ><CircleAlert :size="16" aria-hidden="true" />Eisen / bijzonderheden</Button>
        <AppDialog v-model:visible="particularsVisible" title="Klantbijzonderheden en bestanden">
            <div v-if="notes" class="wrap-anywhere" v-html="notes"></div>
            <ul v-if="clientStore.details?.files?.length">
                <li v-for="(file, index) in clientStore.details.files" :key="index">{{ file.name }}</li>
            </ul>
            <template #footer><button class="button" type="button" @click="particularsVisible = false">Sluiten</button></template>
        </AppDialog>
    </template>
</template>

<style scoped>
.client-info { display:grid; grid-template-columns:auto minmax(0,1fr); gap:5px 12px; margin:0; font-size:11px; }
.client-info dt { color:var(--muted); }
.client-info dd { margin:0; overflow-wrap:anywhere; }
</style>