<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Archive, Barcode, Beaker, ChevronDown, ChevronUp, CircleAlert, FlaskConical, LoaderCircle, RefreshCw, Search, X } from '@lucide/vue';
import Toast from 'openvue/toast';
import { useToast } from 'openvue/usetoast';

const props = defineProps({
    endpoints: { type: Object, required: true },
});

const listTypes = [
    { value: '1', label: 'Standaard' },
    { value: '2', label: 'Legionella' },
    { value: '3', label: 'RODAC' },
];
const barcode = ref('');
const search = ref('');
const listType = ref('1');
const samples = ref([]);
const selectedId = ref(null);
const nextCursor = ref(null);
const hasMore = ref(true);
const loading = ref(false);
const registering = ref(false);
const error = ref('');
const listViewport = ref(null);
const sentinel = ref(null);
const barcodeInput = ref(null);
const toast = useToast();
let observer = null;

const selectedSample = computed(() => samples.value.find((sample) => sample.id === selectedId.value) ?? null);
const standardList = computed(() => listType.value === '1');
const columnCount = computed(() => standardList.value ? 13 : 5);

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
        const requestError = new Error(Object.values(payload.errors ?? {}).flat().join(' ') || payload.message || 'Monsterlijst kon niet worden geladen.');
        requestError.status = response.status;
        requestError.payload = payload;
        throw requestError;
    }

    return payload.data;
}

async function loadPage(reset = false) {
    if (loading.value || (!reset && !hasMore.value)) return;

    if (reset) {
        samples.value = [];
        nextCursor.value = null;
        hasMore.value = true;
        selectedId.value = null;
    }

    loading.value = true;
    error.value = '';
    const params = new URLSearchParams({ type: listType.value });
    if (search.value.trim()) params.set('search', search.value.trim());
    if (nextCursor.value) params.set('before_id', nextCursor.value);

    try {
        const page = await request(`${props.endpoints.data}?${params}`);
        const existingIds = new Set(samples.value.map((sample) => sample.id));
        const newSamples = page.samples.filter((sample) => !existingIds.has(sample.id));
        samples.value = [...samples.value, ...newSamples];
        nextCursor.value = page.next_cursor;
        hasMore.value = page.has_more;
    } catch (requestError) {
        error.value = requestError.message;
    } finally {
        loading.value = false;
    }
}

async function submitSearch() {
    await loadPage(true);
}

async function clearSearch() {
    if (!search.value) return;
    search.value = '';
    await loadPage(true);
}

async function registerSample() {
    if (!barcode.value.trim() || registering.value) return;

    registering.value = true;
    error.value = '';
    let overwrite = false;

    try {
        while (true) {
            try {
                const result = await request(props.endpoints.inoculate, {
                    method: 'POST',
                    body: JSON.stringify({ barcode: barcode.value.trim(), overwrite }),
                });
                const index = samples.value.findIndex((sample) => sample.id === result.sample.id);

                if (index === -1) {
                    samples.value.unshift(result.sample);
                } else {
                    samples.value[index] = result.sample;
                }

                selectedId.value = result.sample.id;
                barcode.value = '';
                toast.add({ severity: 'success', summary: result.message, life: 3500 });
                await nextTick();
                document.getElementById(`sample-register-row-${result.sample.id}`)?.scrollIntoView({ block: 'nearest' });
                barcodeInput.value?.focus();
                break;
            } catch (requestError) {
                if (requestError.status === 409 && requestError.payload?.code === 'already_started' && window.confirm(requestError.payload.message)) {
                    overwrite = true;
                    continue;
                }

                error.value = requestError.message;
                break;
            }
        }
    } finally {
        registering.value = false;
    }
}

function lookupUrl(sampleBarcode) {
    return props.endpoints.lookup.replace('__BARCODE__', encodeURIComponent(sampleBarcode));
}

function selectSample(sample) {
    selectedId.value = selectedId.value === sample.id ? null : sample.id;
}

watch(listType, () => loadPage(true));

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) loadPage();
    }, { root: listViewport.value, rootMargin: '260px' });
    observer.observe(sentinel.value);
    loadPage(true);
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <Toast position="top-right" />
    <div class="sample-register" :aria-busy="loading || registering">
        <p v-if="error" class="notice error" role="alert">{{ error }}</p>
        <div class="sample-register-controls">
            <section class="sample-panel">
                <h2><Barcode :size="16" aria-hidden="true" />Registreer inzetdatum en tijd monster</h2>
                <form class="sample-register-panel-body" @submit.prevent="registerSample">
                    <label class="sr-only" for="register-barcode">Barcode</label>
                    <div class="sample-register-input">
                        <Barcode :size="16" aria-hidden="true" />
                        <input id="register-barcode" ref="barcodeInput" v-model="barcode" type="text" autocomplete="off" placeholder="Barcode" autofocus :disabled="registering">
                    </div>
                    <button class="button primary" type="submit" :disabled="registering || !barcode.trim()"><LoaderCircle v-if="registering" class="spin" :size="16" aria-hidden="true" /><Barcode v-else :size="16" aria-hidden="true" />Registreren</button>
                </form>
            </section>
            <section class="sample-panel">
                <h2><Search :size="16" aria-hidden="true" />Monster opzoeken</h2>
                <form class="sample-register-panel-body sample-register-search" @submit.prevent="submitSearch">
                    <label class="sr-only" for="register-search">Monster zoeken</label>
                    <div class="sample-register-input"><Search :size="16" aria-hidden="true" /><input id="register-search" v-model="search" type="search" autocomplete="off" placeholder="Barcode" :disabled="loading"></div>
                    <button class="icon-button" type="button" title="Zoekveld wissen" aria-label="Zoekveld wissen" :disabled="!search" @click="clearSearch"><X :size="16" /></button>
                    <button class="button primary" type="submit" title="Monster zoeken" :disabled="loading"><Search :size="16" aria-hidden="true" /></button>
                </form>
            </section>
            <section class="sample-panel">
                <h2><FlaskConical :size="16" aria-hidden="true" />Type lijst</h2>
                <div class="sample-register-panel-body"><label class="field"><span>Toon de volgende monsters</span><select v-model="listType" :disabled="loading"><option v-for="type in listTypes" :key="type.value" :value="type.value">{{ type.label }}</option></select></label></div>
            </section>
            <section class="sample-panel">
                <h2><Archive :size="16" aria-hidden="true" />Traceerbaarheid</h2>
                <div class="sample-register-panel-body">
                    <dl class="sample-register-trace">
                        <dt>Monster</dt><dd>{{ selectedSample?.barcode ?? '-' }}</dd>
                        <dt>Opslag</dt><dd>{{ selectedSample?.stored_in || '-' }}</dd>
                        <dt>Afweegstation</dt><dd>{{ selectedSample?.diluted_at || '-' }}</dd>
                    </dl>
                </div>
            </section>
        </div>
        <section class="sample-panel sample-register-list-panel">
            <header class="sample-register-list-heading">
                <div><span class="eyebrow">{{ listTypes.find((type) => type.value === listType)?.label }}</span><h2>Openstaande monsters</h2></div>
                <div class="sample-register-list-actions"><span v-if="loading" class="sample-register-loading" role="status"><LoaderCircle class="spin" :size="15" />Laden...</span><button class="icon-button" type="button" title="Lijst vernieuwen" aria-label="Lijst vernieuwen" :disabled="loading" @click="loadPage(true)"><RefreshCw :size="16" /></button></div>
            </header>
            <div ref="listViewport" class="sample-register-table-wrap">
                <table class="data-table sample-register-table">
                    <thead>
                        <tr><th>Monster #</th><th colspan="2">Ontvangst<br>Datum + tijd</th><th colspan="2">Inzet<br>Datum + tijd</th><template v-if="standardList"><th>Klant</th><th>Omschrijving monster</th><th>L</th><th>S</th><th>C</th><th>ST</th><th>In bak<br>vriezer</th><th>Afweegstation</th></template></tr>
                    </thead>
                    <tbody>
                        <tr v-if="!samples.length && !loading"><td :colspan="columnCount" class="empty-state">Geen monsters gevonden.</td></tr>
                        <tr v-for="sample in samples" :id="`sample-register-row-${sample.id}`" :key="sample.id" :class="{ 'selected-row': selectedId === sample.id }" @click="selectSample(sample)">
                            <td><span v-if="sample.has_note" class="sample-register-warning" title="Monster heeft een notitie"><CircleAlert :size="14" /></span><strong>{{ sample.barcode }}</strong><a class="icon-button sample-register-row-link" :href="lookupUrl(sample.barcode)" title="Monster opzoeken" aria-label="Monster opzoeken" @click.stop><Beaker :size="14" /></a></td>
                            <td>{{ sample.received_date }}</td><td>{{ sample.received_time }}</td>
                            <td :class="{ 'sample-register-started': sample.started }">{{ sample.inoculation_date }}</td><td :class="{ 'sample-register-started': sample.started }">{{ sample.inoculation_time }}</td>
                            <template v-if="standardList"><td>{{ sample.client }}</td><td class="sample-register-description" :title="sample.description">{{ sample.description }}</td><td class="sample-register-flag">{{ sample.flags.listeria ? 'X' : '' }}</td><td class="sample-register-flag">{{ sample.flags.salmonella ? 'X' : '' }}</td><td class="sample-register-flag">{{ sample.flags.campylobacter ? 'X' : '' }}</td><td class="sample-register-flag">{{ sample.flags.stec ? 'X' : '' }}</td><td>{{ sample.stored_in }}</td><td>{{ sample.diluted_at }}</td></template>
                        </tr>
                    </tbody>
                </table>
                <div ref="sentinel" class="sample-register-sentinel" aria-hidden="true"></div>
            </div>
        </section>
        <div class="sample-register-scroll-controls"><button class="icon-button" type="button" title="Naar boven" aria-label="Naar boven" @click="listViewport?.scrollTo({ top: 0, behavior: 'smooth' })"><ChevronUp :size="16" /></button><button class="icon-button" type="button" title="Verder laden" aria-label="Verder laden" :disabled="loading || !hasMore" @click="loadPage()"><ChevronDown :size="16" /></button></div>
    </div>
</template>

<style scoped>
.sample-register { display:grid; gap:14px; max-width:1600px; }
.sample-register-controls { display:grid; grid-template-columns:minmax(270px,1.35fr) minmax(230px,1fr) minmax(180px,.75fr) minmax(180px,.8fr); gap:14px; align-items:stretch; }
.sample-register-panel-body { display:flex; align-items:end; gap:8px; padding:12px; }
.sample-register-panel-body .field { flex:1; }
.sample-register-input { position:relative; display:flex; align-items:center; min-width:0; flex:1; }
.sample-register-input > svg { position:absolute; left:10px; z-index:1; color:var(--muted); pointer-events:none; }
.sample-register-input input { width:100%; min-height:35px; border:1px solid #9eabb2; border-radius:2px; background:var(--surface); color:var(--ink); padding:8px 10px 8px 34px; font:inherit; }
.sample-register-search .icon-button { flex:none; margin:0; color:#903e32; }
.sample-register-trace { display:grid; grid-template-columns:minmax(90px,1fr) minmax(0,1.3fr); gap:7px 12px; margin:0; font-size:11px; }
.sample-register-trace dt { color:var(--muted); }
.sample-register-trace dd { margin:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.sample-register-list-panel { min-width:0; }
.sample-register-list-heading { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:9px 11px; border-bottom:1px solid var(--line); background:var(--surface-alt); }
.sample-register-list-heading h2 { margin:4px 0 0; font-size:14px; }
.sample-register-list-actions { display:flex; align-items:center; gap:8px; }
.sample-register-loading { display:flex; align-items:center; gap:5px; color:var(--muted); font-size:11px; }
.sample-register-list-actions .icon-button { margin:0; }
.sample-register-table-wrap { max-height:calc(100vh - 290px); min-height:320px; overflow:auto; background:var(--surface); }
.sample-register-table { min-width:980px; }
.sample-register-table th { position:sticky; top:0; z-index:2; text-align:center; white-space:normal; }
.sample-register-table td { vertical-align:middle; }
.sample-register-table tbody tr { cursor:pointer; }
.sample-register-table tbody tr:hover,.sample-register-table tbody tr.selected-row { background:var(--accent-faint); }
.sample-register-table td:first-child { min-width:150px; }
.sample-register-table td:first-child strong { vertical-align:middle; }
.sample-register-row-link { width:27px; height:27px; margin:0 0 0 8px; }
.sample-register-warning { display:inline-flex; margin-right:5px; color:#b05c00; vertical-align:-3px; }
.sample-register-description { max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.sample-register-flag { color:var(--accent); font-weight:700; text-align:center; }
.sample-register-started { font-weight:700; }
.sample-register-sentinel { height:2px; }
.sample-register-scroll-controls { display:flex; justify-content:flex-end; gap:6px; }
.sample-register-scroll-controls .icon-button { margin:0; }
@media (max-width:1200px) { .sample-register-controls { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media (max-width:700px) { .sample-register-controls { grid-template-columns:1fr; } .sample-register-panel-body { align-items:stretch; flex-direction:column; } .sample-register-table-wrap { max-height:calc(100vh - 480px); } }
</style>