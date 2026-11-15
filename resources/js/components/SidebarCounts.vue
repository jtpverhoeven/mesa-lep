<script setup>
import { onMounted, ref, watch } from 'vue';
import Column from 'openvue/column';
import DataTable from 'openvue/datatable';
import { LoaderCircle } from '@lucide/vue';
import AppDialog from './AppDialog.vue';
import { useSidebarCountsStore } from '../stores/sidebarCountsStore';

const props = defineProps({
    initial: { type: Object, required: true },
    notInoculatedUrl: { type: String, default: '' },
    canViewProjects: Boolean,
});

const store = useSidebarCountsStore();
const samples = ref([]);
const total = ref(0);
const loading = ref(false);
const error = ref('');

store.hydrate(props.initial);

async function loadNotInoculated() {
    if (!props.notInoculatedUrl) return;
    loading.value = true;
    error.value = '';

    try {
        const response = await fetch(props.notInoculatedUrl, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error();
        const { data } = await response.json();
        samples.value = data.samples;
        total.value = data.total;
    } catch {
        error.value = 'De monsters konden niet worden geladen.';
    } finally {
        loading.value = false;
    }
}

watch(() => store.notInoculatedVisible, (visible) => visible && loadNotInoculated());
watch(() => store.counts.not_inoculated, () => store.notInoculatedVisible && loadNotInoculated());
onMounted(() => store.subscribe());
</script>

<template>
    <AppDialog v-model:visible="store.notInoculatedVisible" title="Monsters (niet ingezet)" width="760px">
        <p v-if="error" class="empty-state" role="alert">{{ error }}</p>
        <div v-else class="table-scroll">
            <DataTable :value="samples" data-key="id" :loading="loading">
                <Column field="barcode" header="Barcode">
                    <template #body="{ data }">
                        <a v-if="canViewProjects && data.project_url" :href="data.project_url"><strong>{{ data.barcode }}</strong></a>
                        <strong v-else>{{ data.barcode }}</strong>
                    </template>
                </Column>
                <Column field="client" header="Klant" />
                <Column field="description" header="Omschrijving" />
                <Column header="Ontvangen"><template #body="{ data }">{{ data.received_date }} {{ data.received_time }}</template></Column>
                <template #empty><span class="empty-state"><LoaderCircle v-if="loading" class="spin" :size="16" aria-hidden="true" /><template v-else>Alle monsters zijn ingezet.</template></span></template>
            </DataTable>
            <p v-if="total > samples.length" class="empty-state">{{ samples.length }} van {{ total }} monsters getoond.</p>
        </div>
    </AppDialog>
</template>
