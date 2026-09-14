<script setup>
import { ref } from 'vue';
import { Search } from '@lucide/vue';
import Column from 'openvue/column';
import DataTable from 'openvue/datatable';

const props = defineProps({
    assays: { type: Array, default: () => [] },
    editUrl: { type: String, required: true },
});

const filters = ref({
    global: { value: '', matchMode: 'contains' },
});

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

function assayEditUrl(assay) {
    return props.editUrl.replace('__ASSAY__', String(assay.id));
}
</script>

<template>
    <div class="assay-directory">
        <div class="directory-toolbar">
            <label class="directory-search">
                <Search :size="16" aria-hidden="true" />
                <span class="sr-only">Analyses zoeken</span>
                <input v-model="filters.global.value" type="search" placeholder="Zoek analyses" autocomplete="off">
            </label>
        </div>

        <div class="table-scroll">
            <DataTable
                v-model:filters="filters"
                :value="props.assays"
                data-key="id"
                :global-filter-fields="['id', 'name', 'assayType', 'typeLabel', 'dilution', 'replicates', 'articleCode']"
                sort-field="name"
                :sort-order="1"
                paginator
                :rows="100"
                :rows-per-page-options="[50, 100, 250]"
                table-class="data-table"
                class="assay-data-table"
                :pt="{ pcPaginator: paginatorPassThrough }"
                :unstyled="true"
            >
                <Column field="id" header="ID" sortable />
                <Column field="name" header="Analyse" sortable>
                    <template #body="{ data }"><strong>{{ data.name }}</strong></template>
                </Column>
                <Column field="assayType" header="Analytisch basistype" sortable />
                <Column field="typeLabel" header="Soort" sortable />
                <Column field="dilution" header="Verdunning" sortable />
                <Column field="replicates" header="Replica's" sortable />
                <Column field="articleCode" header="Artikelcode" sortable />
                <Column header="Acties">
                    <template #body="{ data }"><a class="text-link" :href="assayEditUrl(data)">Bewerken</a></template>
                </Column>
                <template #empty><span class="empty-state">Geen analyses gevonden.</span></template>
            </DataTable>
        </div>
    </div>
</template>