<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { AlertTriangle, Barcode, FlaskConical, LoaderCircle, Plus, RefreshCw, Search, UsersRound, X } from '@lucide/vue';
import Toast from 'openvue/toast';
import { useToast } from 'openvue/usetoast';
import { useSampleResearchStore } from '../stores/sampleResearchStore';
import SampleResearchSelector from './SampleResearchSelector.vue';
import SelectedSampleResearch from './SelectedSampleResearch.vue';

const props = defineProps({ endpoints: { type: Object, required: true } });
const research = useSampleResearchStore();
const toast = useToast();
const selector = ref(null);
const samples = ref([]);
const selectedIds = ref([]);
const search = ref('');
const cursor = ref(null);
const hasMore = ref(true);
const loading = ref(false);
const assigning = ref(false);
const listError = ref('');
const assignmentError = ref('');

const selectedClient = computed(() => samples.value.find((sample) => selectedIds.value.includes(sample.id))?.client ?? null);
const canAssign = computed(() => selectedIds.value.length > 0
    && research.selected.length > 0
    && !research.loading
    && !research.pendingConflict
    && !assigning.value);

research.configure(props.endpoints.options);

watch(() => selectedClient.value?.id ?? null, (clientId) => {
    if (clientId === null) {
        research.reset();
        return;
    }

    void research.load(clientId);
});

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function request(url, options = {}) {
    const headers = {
        Accept: 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        ...(options.method && options.method !== 'GET' ? { 'X-CSRF-TOKEN': csrfToken() } : {}),
        ...(options.headers ?? {}),
    };
    const response = await fetch(url, { ...options, headers });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(Object.values(payload.errors ?? {}).flat().join(' ') || payload.message || 'De monsters konden niet worden bijgewerkt.');
    }

    return payload.data;
}

async function loadPage(reset = false) {
    if (loading.value || (!reset && !hasMore.value)) return;

    if (reset) {
        samples.value = [];
        selectedIds.value = [];
        cursor.value = null;
        hasMore.value = true;
        research.reset();
    }

    loading.value = true;
    listError.value = '';
    const params = new URLSearchParams();
    if (search.value.trim()) params.set('search', search.value.trim());
    if (cursor.value) params.set('after_id', String(cursor.value));

    try {
        const page = await request(`${props.endpoints.data}?${params}`);
        const existingIds = new Set(samples.value.map((sample) => sample.id));
        samples.value = [...samples.value, ...page.samples.filter((sample) => !existingIds.has(sample.id))];
        cursor.value = page.next_cursor;
        hasMore.value = page.has_more;
    } catch (error) {
        listError.value = error.message;
    } finally {
        loading.value = false;
    }
}

function toggleSample(sample, checked) {
    if (checked) {
        if (selectedClient.value && Number(selectedClient.value.id) !== Number(sample.client.id)) return;
        selectedIds.value = [...selectedIds.value, sample.id];
        return;
    }

    selectedIds.value = selectedIds.value.filter((id) => id !== sample.id);
}

async function clearSearch() {
    if (!search.value) return;
    search.value = '';
    await loadPage(true);
}

async function assignResearch() {
    if (!canAssign.value) return;

    assigning.value = true;
    assignmentError.value = '';

    try {
        const result = await request(props.endpoints.store, {
            method: 'POST',
            body: JSON.stringify({ sample_ids: selectedIds.value, analyses: research.payload }),
        });
        const assignedIds = new Set(result.sample_ids.map(Number));
        samples.value = samples.value.filter((sample) => !assignedIds.has(sample.id));
        selectedIds.value = [];
        research.reset();
        toast.add({ severity: 'success', summary: result.message, life: 3500 });
    } catch (error) {
        assignmentError.value = error.message;
    } finally {
        assigning.value = false;
    }
}

onMounted(() => {
    void loadPage(true);
});
</script>

<template>
    <Toast position="top-right" />
    <div class="sample-analysis-assignment" :aria-busy="loading || assigning || research.loading">
        <p v-if="assignmentError" class="notice error" role="alert">{{ assignmentError }}</p>
        <div class="sample-assignment-grid">
            <section class="sample-panel">
                <h2><Barcode :size="16" aria-hidden="true" />Monsters zonder analyses<span class="assignment-loaded-count">{{ samples.length }} geladen</span></h2>
                <div class="sample-panel-body">
                    <div class="directory-toolbar sample-assignment-toolbar">
                        <form class="directory-search" role="search" @submit.prevent="loadPage(true)">
                            <Search :size="16" aria-hidden="true" />
                            <label class="sr-only" for="sample-assignment-search">Zoek monsters</label>
                            <input id="sample-assignment-search" v-model="search" type="search" placeholder="Zoek barcode, omschrijving of klant" autocomplete="off" :disabled="loading || assigning">
                            <button v-if="search" class="icon-button" type="button" title="Zoekopdracht wissen" :disabled="loading || assigning" @click="clearSearch"><X :size="15" /></button>
                            <button class="button primary" type="submit" title="Zoeken" :disabled="loading || assigning"><Search :size="16" /></button>
                        </form>
                        <button class="icon-button" type="button" title="Lijst vernieuwen" :disabled="loading || assigning" @click="loadPage(true)"><RefreshCw :size="15" /></button>
                    </div>

                    <p v-if="selectedClient" class="panel-hint" aria-live="polite">{{ selectedIds.length }} geselecteerd · {{ selectedClient.name }}</p>
                    <p v-else class="panel-hint" aria-live="polite">{{ samples.length }} monsters geladen.</p>
                    <p v-if="listError" class="error-text" role="alert">{{ listError }}</p>

                    <div v-if="loading && !samples.length" class="sample-loading" role="status"><LoaderCircle class="spin" :size="18" />Monsters laden...</div>
                    <div v-else-if="samples.length" class="sample-assignment-table-wrap">
                        <table class="sample-assignment-table">
                            <thead>
                                <tr>
                                    <th class="assignment-select-cell"><span class="sr-only">Selecteren</span></th>
                                    <th class="assignment-warning-cell"><span class="sr-only">Waarschuwingen</span></th>
                                    <th scope="col">Barcode</th>
                                    <th scope="col">Klant</th>
                                    <th scope="col">Omschrijving</th>
                                    <th scope="col">Ontvangst</th>
                                    <th scope="col">Ontvangsttijd</th>
                                    <th scope="col">Inzet datum</th>
                                </tr>
                            </thead>
                            <tbody v-for="(sample, index) in samples" :key="sample.id">
                                <tr :class="{ 'is-striped': index % 2 === 1, 'is-selected': selectedIds.includes(sample.id) }">
                                    <td class="assignment-select-cell"><input type="checkbox" :checked="selectedIds.includes(sample.id)" :disabled="assigning || (selectedClient && Number(selectedClient.id) !== Number(sample.client.id))" :aria-label="`Selecteer monster ${sample.barcode}`" @change="toggleSample(sample, $event.target.checked)"></td>
                                    <td class="assignment-warning-cell">
                                        <span v-if="sample.sample_note" class="assignment-warning" :title="`Monsternotitie: ${sample.sample_note}`" :aria-label="`Monsternotitie: ${sample.sample_note}`"><AlertTriangle :size="15" aria-hidden="true" /></span>
                                        <span v-if="sample.client_has_wishes" class="assignment-warning client-wishes" :title="sample.client_wishes_title || 'Klantnotities of bijlagen beschikbaar'" aria-label="Klantnotities of bijlagen beschikbaar"><UsersRound :size="15" aria-hidden="true" /></span>
                                    </td>
                                    <td class="assignment-barcode"><strong>{{ sample.barcode }}</strong></td>
                                    <td>{{ sample.client.name }}</td>
                                    <td>{{ sample.description || '-' }}</td>
                                    <td>{{ sample.ontvangst || '-' }}</td>
                                    <td>{{ sample.ontvangst_tijd || '-' }}</td>
                                    <td>{{ sample.inzet_datum }}</td>
                                </tr>
                                <tr v-if="sample.show_import_details" class="assignment-import-row" :class="{ 'is-striped': index % 2 === 1, 'is-selected': selectedIds.includes(sample.id) }">
                                    <td colspan="8">
                                        <div class="assignment-import-content">
                                            <strong>Analyses:</strong>
                                            <span class="assignment-imported-assays"><span v-for="(analysis, analysisIndex) in sample.import_requested_analysis" :key="`${analysis.kind}-${analysisIndex}`" class="assignment-imported-assay" :class="`is-${analysis.kind}`">{{ analysis.text }}</span></span>
                                            <strong>Overige analyses/opmerkingen:</strong>
                                            <span>{{ sample.import_other_directions }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else-if="!loading" class="empty-state">Geen monsters gevonden.</p>

                    <div v-if="hasMore" class="sample-assignment-pagination">
                        <button class="button" type="button" :disabled="loading || assigning" @click="loadPage()">{{ loading ? 'Laden...' : 'Meer monsters laden' }}</button>
                    </div>
                </div>
            </section>

            <section class="sample-panel">
                <h2><FlaskConical :size="16" aria-hidden="true" />Onderzoek toevoegen</h2>
                <div class="sample-panel-body sample-assignment-research">
                    <p v-if="selectedClient" class="panel-hint">Klant: <strong>{{ selectedClient.name }}</strong></p>
                    <SampleResearchSelector ref="selector" />
                    <SelectedSampleResearch @edit-profile-assay="(entry, assay) => selector.editProfileAssay(entry, assay)" @edit-roaming="(entry) => selector.editRoaming(entry)" />
                    <div class="form-actions sample-assignment-actions">
                        <button class="button primary" type="button" :disabled="!canAssign" @click="assignResearch"><Plus :size="16" />{{ assigning ? 'Toevoegen...' : `Toevoegen aan ${selectedIds.length} monsters` }}</button>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>