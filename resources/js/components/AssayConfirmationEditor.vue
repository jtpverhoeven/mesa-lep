<script setup>
import { computed, nextTick, ref } from 'vue';
import { ArrowDown, ArrowUp, Plus, Search, Trash2 } from '@lucide/vue';

const props = defineProps({
    enabled: { type: Boolean, default: false },
    type: { type: [Number, String], default: 1 },
    initiation: { type: [Number, String], default: 0 },
    depth: { type: [Number, String], default: 5 },
    script: { type: [Array, Object], default: () => [] },
    support: { type: [Array, Object], default: () => [] },
    media: { type: Array, default: () => [] },
    supportMedia: { type: Array, default: () => [] },
    confirmationTables: { type: Array, default: () => [] },
    selectedTable: { type: [Number, String], default: null },
    disabled: { type: Boolean, default: false },
});

const chainSearch = ref('');
const supportSearch = ref('');
const chainPickerOpen = ref(false);
const supportPickerOpen = ref(false);
const chainSearchInput = ref(null);
const supportSearchInput = ref(null);
const enabled = ref(Boolean(props.enabled));
const mode = ref(String(props.type));
const initiation = ref(String(props.initiation));
const depth = ref(props.depth ?? '');
const selectedTable = ref(props.selectedTable ?? '');

function rowKey() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return `${Date.now()}-${Math.random()}`;
}

function rows(value) {
    return (Array.isArray(value) ? value : Object.values(value ?? {})).map((row) => ({
        key: rowKey(),
        mediaId: row?.mediaId === null || row?.mediaId === undefined ? '' : String(row.mediaId),
        disposition: row?.disposition ?? '',
    }));
}

const chain = ref(rows(props.script));
const support = ref(rows(props.support).map(({ key, mediaId }) => ({ key, mediaId })));

function normalizeMedia(items) {
    return items.map((medium) => ({
        ...medium,
        id: String(medium.id),
        active: medium.active !== false,
    }));
}

const chainMedia = computed(() => normalizeMedia(props.media));
const supportingMedia = computed(() => normalizeMedia(props.supportMedia));

function filterMedia(items, query) {
    const value = query.trim().toLocaleLowerCase();

    if (!value) return items;

    return items.filter((medium) => [medium.name, medium.short_name]
        .filter(Boolean)
        .some((label) => label.toLocaleLowerCase().includes(value)));
}

const filteredChainMedia = computed(() => filterMedia(chainMedia.value, chainSearch.value));
const filteredSupportMedia = computed(() => filterMedia(supportingMedia.value, supportSearch.value));

const serializedScript = computed(() => JSON.stringify(chain.value.map((row, index) => ({
    mediaId: row.mediaId === '' ? null : Number(row.mediaId),
    chainId: index + 1,
    disposition: row.disposition,
}))));

const serializedSupport = computed(() => JSON.stringify(support.value.map((row, index) => ({
    mediaId: row.mediaId === '' ? null : Number(row.mediaId),
    chainId: index + 1,
}))));

function addChain(mediaId = '') {
    chain.value.push({ key: rowKey(), mediaId: String(mediaId), disposition: '' });
}

function addSupport(mediaId = '') {
    support.value.push({ key: rowKey(), mediaId: String(mediaId) });
}

async function openChainPicker() {
    chainPickerOpen.value = true;
    await nextTick();
    chainSearchInput.value?.focus();
}

async function openSupportPicker() {
    supportPickerOpen.value = true;
    await nextTick();
    supportSearchInput.value?.focus();
}

function selectChainMedium(mediaId) {
    addChain(mediaId);
    chainSearch.value = '';
    chainPickerOpen.value = false;
}

function selectSupportMedium(mediaId) {
    addSupport(mediaId);
    supportSearch.value = '';
    supportPickerOpen.value = false;
}

function move(items, index, offset) {
    const target = index + offset;

    if (target < 0 || target >= items.length) return;

    [items[index], items[target]] = [items[target], items[index]];
}

function chainError(row) {
    if (!row.mediaId) return 'Kies een medium.';
    if (!['+', '-', '?'].includes(row.disposition)) return 'Kies een verwachte uitkomst.';

    return '';
}

function mediaLabel(medium) {
    return `${medium.name}${medium.active ? '' : ' (inactief)'}`;
}
</script>

<template>
    <div class="assay-confirmation-editor">
        <div class="assay-confirmation-controls">
            <div class="check-grid">
                <input type="hidden" name="confirmation" value="0">
                <label><input v-model="enabled" type="checkbox" name="confirmation" value="1" :disabled="disabled">Gebruikt bevestiging</label>
            </div>

            <div v-show="enabled" class="assay-confirmation-options">
                <div class="form-grid">
                    <div class="field">
                        <label>Scope bevestiging</label>
                        <div class="assay-confirmation-segments" role="group" aria-label="Scope bevestiging">
                            <button type="button" :class="{ active: mode === '0' }" :aria-pressed="mode === '0'" :disabled="disabled" @click="mode = '0'">Globaal</button>
                            <button type="button" :class="{ active: mode === '1' }" :aria-pressed="mode === '1'" :disabled="disabled" @click="mode = '1'">Per plaat</button>
                        </div>
                        <input type="hidden" name="confirmation_type" :value="mode">
                    </div>
                    <div class="field">
                        <label for="confirmation-initiation">Startstrategie</label>
                        <select id="confirmation-initiation" v-model="initiation" name="confirmation_init" :disabled="disabled">
                            <option value="0">Vragen</option>
                            <option value="1">Standaard aan</option>
                            <option value="2">Standaard uit</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="confirmation-depth">Aantal bevestigingen</label>
                        <input id="confirmation-depth" v-model="depth" type="number" name="confirmation_depth" min="0" :disabled="disabled">
                    </div>
                    <div class="field">
                        <label for="confirmation-table">Bevestigingstabel</label>
                        <select id="confirmation-table" v-model="selectedTable" name="show_conf_table" :disabled="disabled">
                            <option value="">Geen tabel</option>
                            <option v-for="table in confirmationTables" :key="table.id" :value="table.id">{{ table.name || `Tabel ${table.id}` }}</option>
                        </select>
                    </div>
                </div>

                <div class="assay-confirmation-list">
            <div class="assay-confirmation-list-heading">
                <div>
                    <strong>Bevestigingsketen</strong>
                    <span class="muted">Volgorde en verwachte uitkomst</span>
                </div>
                <button class="button" type="button" :disabled="disabled" @click="openChainPicker"><Plus :size="15" aria-hidden="true" />Medium toevoegen</button>
            </div>
            <div v-if="chainPickerOpen" class="assay-confirmation-picker" @focusout="chainPickerOpen = $event.currentTarget.contains($event.relatedTarget)">
                <label class="assay-confirmation-search">
                    <Search :size="15" aria-hidden="true" />
                    <span class="sr-only">Bevestigingsmedia zoeken</span>
                    <input ref="chainSearchInput" v-model="chainSearch" type="search" placeholder="Zoek bevestigingsmedium" @keydown.esc="chainPickerOpen = false">
                </label>
                <div class="assay-confirmation-picker-results" role="listbox" aria-label="Bevestigingsmedia">
                    <button v-for="medium in filteredChainMedia" :key="medium.id" type="button" role="option" @click="selectChainMedium(medium.id)">
                        <span><strong>{{ mediaLabel(medium) }}</strong><small v-if="medium.short_name">{{ medium.short_name }}</small></span>
                        <Plus :size="15" aria-hidden="true" />
                    </button>
                    <p v-if="!filteredChainMedia.length" class="muted">Geen bevestigingsmedia gevonden.</p>
                </div>
            </div>
            <div class="assay-confirmation-rows">
                <div v-for="(row, index) in chain" :key="row.key" class="assay-confirmation-row">
                    <span class="assay-confirmation-index">{{ index + 1 }}</span>
                    <select v-model="row.mediaId" :disabled="disabled" :required="enabled" :aria-label="`Medium rij ${index + 1}`">
                        <option value="">Selecteer medium</option>
                        <option v-for="medium in chainMedia" :key="medium.id" :value="medium.id">{{ mediaLabel(medium) }}</option>
                    </select>
                    <select v-model="row.disposition" :disabled="disabled" :required="enabled" :aria-label="`Uitkomst rij ${index + 1}`">
                        <option value="">Uitkomst</option>
                        <option value="+">+</option>
                        <option value="-">-</option>
                        <option value="?">?</option>
                    </select>
                    <div class="assay-confirmation-row-actions">
                        <button class="icon-button" type="button" title="Omhoog" :aria-label="`Rij ${index + 1} omhoog`" :disabled="disabled || index === 0" @click="move(chain, index, -1)"><ArrowUp :size="14" aria-hidden="true" /></button>
                        <button class="icon-button" type="button" title="Omlaag" :aria-label="`Rij ${index + 1} omlaag`" :disabled="disabled || index === chain.length - 1" @click="move(chain, index, 1)"><ArrowDown :size="14" aria-hidden="true" /></button>
                        <button class="icon-button danger" type="button" title="Verwijderen" :aria-label="`Rij ${index + 1} verwijderen`" :disabled="disabled" @click="chain.splice(index, 1)"><Trash2 :size="14" aria-hidden="true" /></button>
                    </div>
                    <p v-if="chainError(row)" class="error-text">{{ chainError(row) }}</p>
                </div>
                <p v-if="!chain.length" class="assay-confirmation-empty">Nog geen bevestigingsmedia geselecteerd.</p>
            </div>
            <input type="hidden" name="confirmation_script" :value="serializedScript">
            </div>

            <div class="assay-confirmation-list">
            <div class="assay-confirmation-list-heading">
                <div>
                    <strong>Ondersteunende media</strong>
                    <span class="muted">Optionele media buiten de keten</span>
                </div>
                <button class="button" type="button" :disabled="disabled" @click="openSupportPicker"><Plus :size="15" aria-hidden="true" />Medium toevoegen</button>
            </div>
            <div v-if="supportPickerOpen" class="assay-confirmation-picker" @focusout="supportPickerOpen = $event.currentTarget.contains($event.relatedTarget)">
                <label class="assay-confirmation-search">
                    <Search :size="15" aria-hidden="true" />
                    <span class="sr-only">Ondersteunende media zoeken</span>
                    <input ref="supportSearchInput" v-model="supportSearch" type="search" placeholder="Zoek ondersteunend medium" @keydown.esc="supportPickerOpen = false">
                </label>
                <div class="assay-confirmation-picker-results" role="listbox" aria-label="Ondersteunende media">
                    <button v-for="medium in filteredSupportMedia" :key="medium.id" type="button" role="option" @click="selectSupportMedium(medium.id)">
                        <span><strong>{{ mediaLabel(medium) }}</strong><small v-if="medium.short_name">{{ medium.short_name }}</small></span>
                        <Plus :size="15" aria-hidden="true" />
                    </button>
                    <p v-if="!filteredSupportMedia.length" class="muted">Geen ondersteunende media gevonden.</p>
                </div>
            </div>
            <div class="assay-confirmation-rows">
                <div v-for="(row, index) in support" :key="row.key" class="assay-confirmation-row support-row">
                    <span class="assay-confirmation-index">{{ index + 1 }}</span>
                    <select v-model="row.mediaId" :disabled="disabled" :required="enabled" :aria-label="`Ondersteunend medium rij ${index + 1}`">
                        <option value="">Selecteer medium</option>
                        <option v-for="medium in supportingMedia" :key="medium.id" :value="medium.id">{{ mediaLabel(medium) }}</option>
                    </select>
                    <div class="assay-confirmation-row-actions">
                        <button class="icon-button" type="button" title="Omhoog" :aria-label="`Ondersteunend medium ${index + 1} omhoog`" :disabled="disabled || index === 0" @click="move(support, index, -1)"><ArrowUp :size="14" aria-hidden="true" /></button>
                        <button class="icon-button" type="button" title="Omlaag" :aria-label="`Ondersteunend medium ${index + 1} omlaag`" :disabled="disabled || index === support.length - 1" @click="move(support, index, 1)"><ArrowDown :size="14" aria-hidden="true" /></button>
                        <button class="icon-button danger" type="button" title="Verwijderen" :aria-label="`Ondersteunend medium ${index + 1} verwijderen`" :disabled="disabled" @click="support.splice(index, 1)"><Trash2 :size="14" aria-hidden="true" /></button>
                    </div>
                </div>
                <p v-if="!support.length" class="assay-confirmation-empty">Geen ondersteunende media geselecteerd.</p>
            </div>
            <input type="hidden" name="confirmation_support" :value="serializedSupport">
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.assay-confirmation-editor { display:grid; gap:18px; max-width:1050px; }
.assay-confirmation-controls { display:grid; gap:16px; }
.assay-confirmation-options { display:grid; gap:18px; }
.assay-confirmation-segments { display:grid; grid-template-columns:1fr 1fr; }
.assay-confirmation-segments button { min-height:35px; border:1px solid #9eabb2; margin-left:-1px; background:var(--surface); color:var(--muted); font:inherit; font-weight:600; cursor:pointer; }
.assay-confirmation-segments button:first-child { margin-left:0; border-radius:2px 0 0 2px; }
.assay-confirmation-segments button:last-child { border-radius:0 2px 2px 0; }
.assay-confirmation-segments button.active { position:relative; border-color:var(--accent); background:var(--accent-faint); color:var(--accent); }
.assay-confirmation-list { display:grid; gap:10px; padding-top:14px; border-top:1px solid var(--line); }
.assay-confirmation-list-heading { display:flex; align-items:center; justify-content:space-between; gap:12px; }
.assay-confirmation-list-heading > div { display:grid; gap:3px; }
.assay-confirmation-list-heading .muted { font-size:11px; }
.assay-confirmation-picker { border:1px solid #9eabb2; background:var(--surface); box-shadow:0 6px 18px rgba(20,35,45,.14); }
.assay-confirmation-search { position:relative; display:flex; align-items:center; color:var(--muted); }
.assay-confirmation-search svg { position:absolute; left:11px; pointer-events:none; }
.assay-confirmation-search input { width:100%; min-height:38px; border:0; border-bottom:1px solid var(--line); background:var(--surface); color:var(--ink); padding:8px 10px 8px 35px; font:inherit; }
.assay-confirmation-picker-results { display:grid; max-height:220px; overflow-y:auto; }
.assay-confirmation-picker-results button { display:flex; align-items:center; justify-content:space-between; gap:12px; min-height:45px; padding:7px 10px; border:0; border-bottom:1px solid var(--line); background:var(--surface); color:var(--ink); text-align:left; font:inherit; cursor:pointer; }
.assay-confirmation-picker-results button:hover,.assay-confirmation-picker-results button:focus-visible { background:var(--accent-faint); color:var(--accent); }
.assay-confirmation-picker-results button > span { display:flex; flex-direction:column; gap:2px; min-width:0; }
.assay-confirmation-picker-results strong,.assay-confirmation-picker-results small { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.assay-confirmation-picker-results small { color:var(--muted); }
.assay-confirmation-picker-results button > svg { flex:none; }
.assay-confirmation-picker-results p { margin:0; padding:14px 10px; font-size:11px; }
.assay-confirmation-rows { display:grid; gap:8px; }
.assay-confirmation-row { display:grid; grid-template-columns:34px minmax(150px,1fr) minmax(100px,160px) auto; align-items:center; gap:8px; }
.assay-confirmation-row.support-row { grid-template-columns:34px minmax(150px,1fr) auto; }
.assay-confirmation-row select { width:100%; min-height:35px; border:1px solid #9eabb2; border-radius:2px; background:var(--surface); color:var(--ink); padding:7px 9px; font:inherit; }
.assay-confirmation-index { display:grid; place-items:center; width:28px; height:28px; border:1px solid var(--line); background:var(--surface-alt); color:var(--muted); font-weight:700; }
.assay-confirmation-row-actions { display:flex; gap:3px; }
.assay-confirmation-row-actions .icon-button { margin:0; }
.assay-confirmation-row .error-text { grid-column:2/-1; }
.assay-confirmation-empty { margin:0; padding:12px; border:1px dashed var(--line); color:var(--muted); font-size:11px; }
@media (max-width:700px) {
    .assay-confirmation-list-heading { align-items:flex-start; flex-direction:column; }
    .assay-confirmation-row,.assay-confirmation-row.support-row { grid-template-columns:30px minmax(0,1fr) auto; }
    .assay-confirmation-row select:nth-child(3) { grid-column:2; }
    .assay-confirmation-row-actions { grid-column:3; grid-row:1/span 2; flex-direction:column; }
    .assay-confirmation-row .error-text { grid-column:2/-1; }
}
</style>