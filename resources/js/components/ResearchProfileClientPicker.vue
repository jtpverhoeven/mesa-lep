<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { LoaderCircle, Search, X } from '@lucide/vue';
import { useClientSelectorStore } from '../stores/clientSelectorStore';

const props = defineProps({ endpoint: { type: String, required: true }, modelValue: { type: Object, default: null } });
const emit = defineEmits(['update:modelValue']);
const store = useClientSelectorStore();
const open = ref(false);
const activeIndex = ref(-1);
let timer;
const activeResultId = computed(() => activeIndex.value >= 0 ? `profile-client-${store.results[activeIndex.value]?.id}` : undefined);

store.configure(props.endpoint);
watch(() => props.modelValue, (client) => client ? store.select(client) : store.clear(), { immediate: true });

function inputChanged(event) {
    const query = event.target.value;
    if (store.selected && query !== store.selected.name) emit('update:modelValue', null);
    store.query = query;
    open.value = true;
    activeIndex.value = -1;
    window.clearTimeout(timer);
    timer = window.setTimeout(() => store.search(query), 350);
}
function select(client) { store.select(client); emit('update:modelValue', client); open.value = false; }
function clear() { store.clear(); emit('update:modelValue', null); }
function move(direction) { if (store.results.length) activeIndex.value = (activeIndex.value + direction + store.results.length) % store.results.length; }
function selectActive() { if (activeIndex.value >= 0) select(store.results[activeIndex.value]); }
onBeforeUnmount(() => window.clearTimeout(timer));
</script>

<template>
    <div class="field client-combobox">
        <label for="profile-client">Klant</label>
        <div class="client-search-control"><Search :size="16" /><input id="profile-client" :value="store.query" type="search" autocomplete="off" placeholder="Typ minimaal 2 tekens" role="combobox" :aria-expanded="open" :aria-activedescendant="activeResultId" @input="inputChanged" @focus="open = true" @blur="open = false" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.enter.prevent="selectActive"><LoaderCircle v-if="store.loading" class="spin" :size="16" /><button v-else-if="store.query" class="client-clear" type="button" title="Klant wissen" @mousedown.prevent="clear"><X :size="16" /></button></div>
        <div v-if="open && store.query.trim().length >= 2" class="client-results" role="listbox"><button v-for="(client, index) in store.results" :id="`profile-client-${client.id}`" :key="client.id" type="button" :class="{ active: index === activeIndex }" @mousedown.prevent="select(client)" @mouseenter="activeIndex = index"><strong>{{ client.name }}</strong><small>{{ client.reference }}</small></button><p v-if="!store.loading && !store.results.length" class="client-results-empty">Geen klanten gevonden.</p></div>
    </div>
</template>