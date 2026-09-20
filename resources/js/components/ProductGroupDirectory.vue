<script setup>
import { onMounted, ref, watch } from 'vue';
import { Search } from '@lucide/vue';
import Column from 'openvue/column';
import DataTable from 'openvue/datatable';

const props = defineProps({ dataUrl: { type: String, required: true } });
const groups = ref([]);
const loading = ref(false);
const error = ref('');
const search = ref('');
const hideDefaults = ref(false);
const first = ref(0);
const rows = ref(50);
const totalRecords = ref(0);
let searchTimeout;

const paginatorButtonClass = 'grid h-[30px] min-w-[30px] place-items-center rounded-[2px] border border-[#9eabb2] bg-[var(--surface)] px-2 font-[inherit] text-[var(--accent)] transition-colors hover:bg-[var(--accent-faint)] disabled:cursor-not-allowed disabled:opacity-50';
const paginatorPassThrough = {
    root: { class: 'flex items-center justify-end border-t border-[var(--line)] bg-[var(--surface-alt)] px-3 py-2 text-[var(--muted)]' },
    content: { class: 'flex w-full flex-wrap items-center justify-end gap-1' },
    pages: { class: 'flex items-center gap-1' },
    first: { class: paginatorButtonClass },
    prev: { class: paginatorButtonClass },
    next: { class: paginatorButtonClass },
    last: { class: paginatorButtonClass },
    firstIcon: { class: 'size-3' },
    prevIcon: { class: 'size-3' },
    nextIcon: { class: 'size-3' },
    lastIcon: { class: 'size-3' },
    page: { class: `${paginatorButtonClass} data-[p-active=true]:border-[var(--accent)] data-[p-active=true]:bg-[var(--accent)] data-[p-active=true]:font-bold data-[p-active=true]:text-white data-[p-active=true]:hover:bg-[var(--accent)]` },
    pcRowPerPageDropdown: {
        root: { class: 'relative flex h-[30px] min-w-[64px] items-center rounded-[2px] border border-[#9eabb2] bg-[var(--surface)] text-[var(--ink)] font-[inherit] transition-colors hover:border-[var(--accent)] focus-within:outline-2 focus-within:outline-[var(--accent)] focus-within:outline-offset-2' },
        label: { class: 'flex-1 px-2 py-1 text-left' },
        dropdown: { class: 'grid h-full w-7 place-items-center border-l border-[var(--line)] text-[var(--muted)]' },
        dropdownIcon: { class: 'size-3' },
        overlay: { class: 'z-50 overflow-hidden rounded-[2px] border border-[var(--line)] bg-[var(--surface)] text-[var(--ink)] shadow-lg' },
        list: { class: 'm-0 max-h-60 list-none overflow-auto p-1' },
        option: { class: 'cursor-pointer rounded-[2px] px-2 py-1 hover:bg-[var(--accent-faint)] data-[p-focused=true]:bg-[var(--accent-faint)] data-[p-selected=true]:font-bold' },
    },
};

async function load(page = Math.floor(first.value / rows.value) + 1) {
    loading.value = true;
    error.value = '';
    try {
        const query = new URLSearchParams({ page: String(page), per_page: String(rows.value), hide_default: hideDefaults.value ? '1' : '0' });
        if (search.value.trim()) query.set('search', search.value.trim());
        const response = await fetch(`${props.dataUrl}?${query}`, { headers: { Accept: 'application/json' } });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Productgroepen konden niet worden geladen.');
        groups.value = result.data;
        totalRecords.value = result.meta.total;
        first.value = (result.meta.current_page - 1) * result.meta.per_page;
    } catch (exception) {
        error.value = exception.message;
    } finally {
        loading.value = false;
    }
}

function onPage(event) {
    first.value = event.first;
    rows.value = event.rows;
    load(Math.floor(event.first / event.rows) + 1);
}

watch([search, hideDefaults], () => {
    window.clearTimeout(searchTimeout);
    searchTimeout = window.setTimeout(() => {
        first.value = 0;
        load(1);
    }, 250);
});
onMounted(load);
</script>

<template>
    <section class="product-group-directory sample-panel" :aria-busy="loading">
        <div class="sample-panel-body">
            <p v-if="error" class="error-text" role="alert">{{ error }}</p>
            <div class="directory-toolbar">
                <label class="directory-search"><Search :size="16" aria-hidden="true" /><span class="sr-only">Productgroepen zoeken</span><input v-model="search" type="search" placeholder="Zoek op productgroep of klant" autocomplete="off"></label>
                <label class="directory-toggle"><input v-model="hideDefaults" type="checkbox">Standaardgroepen verbergen</label>
            </div>
            <div class="table-scroll">
                <DataTable :value="groups" :lazy="true" :loading="loading" :first="first" :rows="rows" :total-records="totalRecords" data-key="id" paginator :rows-per-page-options="[50, 100, 250]" table-class="data-table" :pt="{ pcPaginator: paginatorPassThrough }" :unstyled="true" @page="onPage">
                    <Column field="name" header="Productgroep" />
                    <Column field="client" header="Klant" />
                    <Column field="portal_id" header="Portal ID" />
                    <Column header="Standaard"><template #body="{ data }">{{ data.default ? 'Ja' : 'Nee' }}</template></Column>
                    <Column header="Zichtbaar"><template #body="{ data }">{{ data.visible ? 'Ja' : 'Nee' }}</template></Column>
                    <template #empty><span class="empty-state">Geen productgroepen gevonden.</span></template>
                </DataTable>
            </div>
        </div>
    </section>
</template>

<style>
.product-group-directory .directory-toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:12px; }
.directory-toggle { display:flex; align-items:center; gap:6px; font-size:12px; white-space:nowrap; }
@media (max-width:700px) { .product-group-directory .directory-toolbar { align-items:stretch; flex-direction:column; } }
</style>