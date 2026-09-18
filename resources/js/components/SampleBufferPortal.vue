<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { ArchiveRestore, Beaker, CalendarClock, CheckCheck, Droplets, FlaskConical, LoaderCircle, Pencil, Play, Save, Search, SquareStack, X } from '@lucide/vue';
import ConfirmDialog from 'openvue/confirmdialog';
import Toast from 'openvue/toast';
import { useConfirm } from 'openvue/useconfirm';
import { useToast } from 'openvue/usetoast';
import AppDialog from './AppDialog.vue';
import SampleBufferActionDialog from './SampleBufferActionDialog.vue';
import { useSampleBufferStore } from '../stores/sampleBufferStore';
import { createAppDialogPassThrough } from '../dialogPassThrough';

const props = defineProps({ endpoints: { type: Object, required: true }, initialTab: { type: String, default: 'normal' } });
const store = useSampleBufferStore();
const confirm = useConfirm();
const toast = useToast();
const action = ref('');
const actionDialogOpen = ref(false);
const metadataDialogOpen = ref(false);
const metadataRow = ref(null);
const metadataDraft = ref([]);
const metadataIsList = ref(false);
const metadataEdit = ref(null);
const metadataEditValue = ref('');
const confirmDialogPassThrough = createAppDialogPassThrough({ titleId: 'sample-buffer-confirm-title' });
const tabs = [
    { id: 'normal', label: 'Algemeen', icon: Beaker },
    { id: 'tht', label: 'THT onderzoeken', icon: CalendarClock },
    { id: 'legionella', label: 'Legionella', icon: Droplets },
    { id: 'rodac', label: 'Rodac', icon: SquareStack },
];
const actions = computed(() => {
    if (store.tab === 'staged_tht') return [
        ['commit', 'Aanmelden'], ['delete', 'Verwijderen'], ['tht_date', 'Inzetdatum THT wijzigen'], ['storage', 'Bewaartemperatuur wijzigen'], ['receive', 'Ontvangstdatum/tijd wijzigen'],
    ];
    const common = [['commit', 'Goedkeuren & aanmelden'], ['sampling_date', 'Bemonsterdatum wijzigen'], ['sampling_method', 'Bemonsteringsprocedure wijzigen'], ['delete', 'Verwijderen']];
    if (store.tab === 'normal') common.splice(3, 0, ['move_to_tht', 'Verplaatsen naar THT-lijst']);
    if (store.tab === 'tht') common.splice(3, 0, ['tht_date', 'Inzetdatum THT wijzigen']);
    return common;
});
const showTht = computed(() => ['tht', 'staged_tht'].includes(store.tab));
const title = computed(() => store.tab === 'staged_tht' ? 'THT onderzoeken' : 'Voorportaal');
let searchTimer = null;

function runSelectedAction() {
    if (!action.value || !store.selected.length) return;
    if (action.value === 'delete') {
        confirm.require({ message: `${store.selected.length} bufferregel(s) definitief verwijderen?`, header: 'Verwijderen bevestigen', acceptLabel: 'Verwijderen', rejectLabel: 'Annuleren', acceptClass: 'button primary', rejectClass: 'button', accept: () => execute({}) });
        return;
    }
    if (action.value === 'commit' && store.tab === 'staged_tht') {
        execute({});
        return;
    }
    actionDialogOpen.value = true;
}

async function execute(values) {
    actionDialogOpen.value = false;
    const result = await store.execute(action.value, values);
    if (result) {
        toast.add({ severity: 'success', summary: result.message, life: 3500 });
        action.value = '';
    }
}

function editMetadata(row) {
    metadataRow.value = row;
    metadataIsList.value = Array.isArray(row.meta);
    metadataDraft.value = metadataIsList.value
        ? row.meta.map(([name, value]) => ({ name, value: String(value ?? '') }))
        : Object.entries(row.meta ?? {}).map(([name, value]) => ({ name, value: String(value ?? '') }));
    metadataEdit.value = null;
    metadataDialogOpen.value = true;
}

function startMetadataEdit(index, property) {
    metadataEdit.value = { index, property };
    metadataEditValue.value = metadataDraft.value[index][property];
}

function cancelMetadataEdit() {
    metadataEdit.value = null;
    metadataEditValue.value = '';
}

async function saveMetadataEdit() {
    const { index, property } = metadataEdit.value;
    const updatedFields = metadataDraft.value.map((field, fieldIndex) => fieldIndex === index
        ? { ...field, [property]: metadataEditValue.value }
        : field);
    const fields = updatedFields.map((field) => [field.name, field.value]);
    const meta = metadataIsList.value ? fields : Object.fromEntries(fields);
    const result = await store.execute('metadata', { meta }, [metadataRow.value.id]);
    if (result) {
        metadataDraft.value = updatedFields;
        cancelMetadataEdit();
        toast.add({ severity: 'success', summary: result.message, life: 3500 });
    }
}

watch(() => store.search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => store.load(), 250);
});
store.configure(props.endpoints, props.initialTab);
onMounted(() => store.load());
</script>

<template>
    <Toast position="top-right" /><ConfirmDialog :draggable="false" :unstyled="true" :pt="confirmDialogPassThrough" />
    <div class="sample-buffer-workspace">
        <div class="page-heading buffer-heading"><div><div class="eyebrow">Laboratorium / Monsters</div><h1>{{ title }}</h1></div><span class="buffer-total">{{ store.rows.length }} zichtbaar</span></div>
        <div v-if="store.error" class="notice error" role="alert">{{ store.error }}</div>
        <nav v-if="initialTab !== 'staged_tht'" class="buffer-tabs" aria-label="Voorportaal categorieën">
            <button v-for="tab in tabs" :key="tab.id" type="button" :aria-selected="store.tab === tab.id" @click="store.setTab(tab.id)"><component :is="tab.icon" :size="16" />{{ tab.label }}<span>{{ store.counts[tab.id] ?? 0 }}</span></button>
        </nav>
        <section class="sample-panel buffer-panel">
            <div class="buffer-toolbar">
                <label><span>Met geselecteerd:</span><select v-model="action"><option value="">-- Acties --</option><option v-for="item in actions" :key="item[0]" :value="item[0]">{{ item[1] }}</option></select></label>
                <button class="button primary" type="button" :disabled="!action || !store.selected.length || store.mutating" @click="runSelectedAction"><LoaderCircle v-if="store.mutating" class="spin" :size="16" /><Play v-else :size="16" />Uitvoeren</button>
                <label class="buffer-sort"><span>Sorteer op:</span><select v-model="store.sort"><option value="sampling_date">Bemonsterdatum</option><option value="client">Klant</option><option value="project_name">Projectnaam</option><option value="project">Project-slug</option><option v-if="showTht" value="tht_date">Inzetdatum</option><option v-if="store.tab === 'staged_tht'" value="tht_code">THT-code</option></select><select v-model="store.direction"><option value="asc">Oplopend</option><option value="desc">Aflopend</option></select><button class="icon-button" type="button" title="Sortering toepassen" @click="store.load"><ArchiveRestore :size="16" /></button></label>
                <label class="buffer-search"><Search :size="16" /><span class="sr-only">Zoeken</span><input v-model="store.search" type="search" placeholder="Zoeken in tabel"></label>
                <button class="button" type="button" :disabled="!store.selected.length" @click="store.expandProjects"><SquareStack :size="16" />Selectie uitbreiden naar project</button>
            </div>
            <div class="table-scroll buffer-table-wrap">
                <table class="data-table buffer-table">
                    <thead><tr><th><button class="text-button" type="button" @click="store.selectAll">[Project]</button></th><th>Bron</th><th v-if="store.tab === 'staged_tht'">THT-code</th><th>Klant</th><th>Project</th><th>Projectnaam</th><th>Volgnummer</th><th>Bemonsterdatum</th><th>Bemonsteringsprocedure</th><th>Omschrijving</th><th v-if="store.tab === 'rodac'">Ruimte</th><th v-if="store.tab === 'legionella'">Watertype / matrix</th><th>Monsterdetails</th><th v-if="store.tab === 'staged_tht'">Ontvangst datum [tijd]</th><th v-if="showTht">Inzetdatum</th><th>Metadata</th></tr></thead>
                    <tbody>
                        <tr v-if="store.loading"><td :colspan="15" class="empty-state"><LoaderCircle class="spin" :size="18" /> Laden</td></tr>
                        <tr v-else-if="!store.rows.length"><td :colspan="15" class="empty-state">Geen bufferregels gevonden.</td></tr>
                        <tr v-for="row in store.rows" v-else :key="row.id" :class="{ 'selected-row': store.selected.includes(row.id) }">
                            <td><input type="checkbox" :checked="store.selected.includes(row.id)" :aria-label="`${row.project} selecteren`" @change="store.toggle(row.id)"></td><td><span class="source-badge" :title="({ 1: 'CSV import', 2: 'LIMS', 3: 'Portal' })[row.source]">{{ ({ 1: 'CSV', 2: 'LIMS', 3: 'Portal' })[row.source] ?? row.source }}</span></td><td v-if="store.tab === 'staged_tht'"><strong>{{ row.tht_code }}</strong></td><td>{{ row.client_name }}</td><td>{{ row.project }}</td><td>{{ row.project_name || '-' }}</td><td>{{ row.portal_follow_no || '-' }}</td><td>{{ row.sampling_date }}</td><td>{{ row.sampling_method_name }}</td><td>{{ row.sample_name }}</td><td v-if="store.tab === 'rodac'">{{ row.room || '-' }}</td><td v-if="store.tab === 'legionella'">{{ [row.water_type, row.matrix_type].filter(Boolean).join(' / ') || '-' }}</td><td>{{ row.sample_details || '-' }}</td><td v-if="store.tab === 'staged_tht'">{{ row.receive_date || '-' }}<br><small>{{ row.receive_time || '-' }}</small></td><td v-if="showTht" :class="`tht-${row.tht_day_type || 'weekday'}`">{{ row.tht_date || '-' }}</td><td><button class="icon-button" type="button" title="Metadata bekijken en wijzigen" @click="editMetadata(row)"><FlaskConical :size="15" /></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <footer class="buffer-footer"><button class="text-button" type="button" @click="store.selectAll"><CheckCheck :size="15" />Alles selecteren</button><span>{{ store.selected.length }} geselecteerd</span></footer>
        </section>
    </div>
    <SampleBufferActionDialog v-model:visible="actionDialogOpen" :action="action" :sampling-methods="store.samplingMethods" :loading="store.mutating" @submit="execute" />
    <AppDialog v-model:visible="metadataDialogOpen" title="Metadata bekijken / wijzigen" width="620px">
        <div class="table-scroll metadata-table-wrap">
            <table class="data-table metadata-table">
                <thead><tr><th>Meta naam</th><th>Waarde</th></tr></thead>
                <tbody>
                    <tr v-if="!metadataDraft.length"><td colspan="2" class="empty-state">Geen metadata aanwezig.</td></tr>
                    <tr v-for="(field, index) in metadataDraft" v-else :key="index">
                        <td>
                            <div v-if="metadataEdit?.index === index && metadataEdit.property === 'name'" class="metadata-cell-editor"><input v-model="metadataEditValue" :aria-label="`Metadatanaam ${index + 1}`" autofocus><button class="icon-button primary" type="button" title="Naam opslaan" :disabled="store.mutating" @click="saveMetadataEdit"><LoaderCircle v-if="store.mutating" class="spin" :size="15" /><Save v-else :size="15" /></button><button class="icon-button" type="button" title="Bewerken annuleren" :disabled="store.mutating" @click="cancelMetadataEdit"><X :size="15" /></button></div>
                            <div v-else class="metadata-cell-readonly"><span>{{ field.name }}</span><button v-if="metadataEdit === null" class="icon-button" type="button" title="Metadatanaam bewerken" @click="startMetadataEdit(index, 'name')"><Pencil :size="15" /></button></div>
                        </td>
                        <td>
                            <div v-if="metadataEdit?.index === index && metadataEdit.property === 'value'" class="metadata-cell-editor"><textarea v-model="metadataEditValue" :aria-label="`Metadatawaarde ${field.name}`" rows="3" autofocus></textarea><button class="icon-button primary" type="button" title="Waarde opslaan" :disabled="store.mutating" @click="saveMetadataEdit"><LoaderCircle v-if="store.mutating" class="spin" :size="15" /><Save v-else :size="15" /></button><button class="icon-button" type="button" title="Bewerken annuleren" :disabled="store.mutating" @click="cancelMetadataEdit"><X :size="15" /></button></div>
                            <div v-else class="metadata-cell-readonly"><span class="metadata-value">{{ field.value }}</span><button v-if="metadataEdit === null" class="icon-button" type="button" title="Metadatawaarde bewerken" @click="startMetadataEdit(index, 'value')"><Pencil :size="15" /></button></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <template #footer><button class="button" type="button" @click="metadataDialogOpen = false">Sluiten</button></template>
    </AppDialog>
</template>

<style scoped>
.buffer-heading { margin-bottom:0; }
.buffer-total { color:var(--muted); font-size:11px; }
.buffer-tabs { display:flex; overflow:auto; border:1px solid var(--line); border-bottom:0; background:var(--surface); }
.buffer-tabs button { display:flex; align-items:center; gap:7px; min-height:40px; padding:7px 14px; border:0; border-right:1px solid var(--line); border-bottom:2px solid transparent; background:var(--surface); color:var(--muted); font:inherit; font-weight:600; white-space:nowrap; cursor:pointer; }
.buffer-tabs button[aria-selected='true'] { border-bottom-color:var(--accent); background:var(--accent-faint); color:var(--accent); }
.buffer-tabs span { min-width:21px; padding:2px 5px; background:#d8e0e4; color:#34454e; font-size:10px; text-align:center; }
.buffer-panel { border-radius:0; }
.buffer-toolbar { display:flex; align-items:end; flex-wrap:wrap; gap:9px; padding:10px; border-bottom:1px solid var(--line); background:var(--surface-alt); }
.buffer-toolbar label { display:flex; align-items:center; gap:7px; font-size:11px; font-weight:600; }
.buffer-toolbar select,.buffer-toolbar input { min-height:34px; border:1px solid #9eabb2; border-radius:2px; background:var(--surface); color:var(--ink); padding:6px 8px; font:inherit; }
.buffer-sort { margin-left:auto; }
.buffer-search { position:relative; flex:1 1 100%; }
.buffer-search svg { position:absolute; left:10px; color:var(--muted); }
.buffer-search input { width:min(540px,100%); padding-left:33px; }
.buffer-table-wrap { max-height:calc(100vh - 270px); min-height:330px; border:0; }
.buffer-table { min-width:1280px; }
.buffer-table th,.buffer-table td { padding:8px 9px; vertical-align:top; }
.buffer-table tbody tr.selected-row { background:var(--accent-faint); }
.source-badge { display:inline-block; padding:2px 5px; border:1px solid var(--line); background:var(--surface-alt); font-size:10px; }
.tht-saturday { background:#f4b95f; color:#422b05; font-weight:700; }.tht-sunday { background:#b84b41; color:white; font-weight:700; }
.buffer-footer { display:flex; justify-content:space-between; padding:9px 11px; border-top:1px solid var(--line); color:var(--muted); font-size:11px; }.buffer-footer button { display:inline-flex; align-items:center; gap:5px; }
.metadata-table-wrap { border:1px solid var(--line); }
.metadata-table { width:100%; table-layout:fixed; }
.metadata-table th:first-child { width:38%; }
.metadata-table td { vertical-align:top; }
.metadata-cell-readonly { display:flex; align-items:start; justify-content:space-between; gap:8px; min-height:32px; }
.metadata-cell-readonly > span { min-width:0; padding:6px 0; overflow-wrap:anywhere; }
.metadata-value { white-space:pre-wrap; }
.metadata-cell-editor { display:grid; grid-template-columns:minmax(0,1fr) 32px 32px; gap:6px; align-items:start; }
.metadata-cell-editor input,.metadata-cell-editor textarea { width:100%; min-height:32px; border:1px solid #9eabb2; border-radius:2px; background:var(--surface); color:var(--ink); padding:6px 8px; font:inherit; resize:vertical; }
@media (max-width:760px) { .buffer-sort { margin-left:0; flex-wrap:wrap; }.buffer-toolbar > label:first-child { flex:1 1 100%; }.buffer-toolbar > label:first-child select { flex:1; }.buffer-table-wrap { max-height:calc(100vh - 340px); } }
</style>