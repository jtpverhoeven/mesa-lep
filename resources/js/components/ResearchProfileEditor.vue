<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { ArrowDown, ArrowUp, Beaker, ClipboardCopy, Info, LoaderCircle, Plus, Save, Search, Trash2 } from '@lucide/vue';
import ResearchProfileClientPicker from './ResearchProfileClientPicker.vue';
import { useResearchProfileStore } from '../stores/researchProfileStore';

const props = defineProps({ endpoints: { type: Object, required: true } });
const store = useResearchProfileStore();
const form = reactive({ name: '', global: true, client: null, portal_visible: false, lims_visible: true, assays: [] });
const search = ref('');
const matrix = ref('');
const sourceId = ref('');
const sourceQuery = ref('');

const availableAssays = computed(() => store.assays.filter((assay) => {
    if (form.assays.some((selected) => Number(selected.assay) === Number(assay.id))) return false;
    if (search.value && !assay.name.toLowerCase().includes(search.value.toLowerCase()) && !String(assay.id).includes(search.value)) return false;
    return !matrix.value || assay.matrices?.map(Number).includes(Number(matrix.value));
}));
const copyProfiles = computed(() => store.profiles.filter((profile) => !sourceQuery.value || profile.name.toLowerCase().includes(sourceQuery.value.toLowerCase()) || String(profile.id).includes(sourceQuery.value)));

function applyProfile(profile) {
    if (!profile) return;
    Object.assign(form, {
        name: profile.name, global: Boolean(profile.global), client: profile.client_record,
        portal_visible: Boolean(profile.portal_visible), lims_visible: Boolean(profile.lims_visible),
        assays: profile.assays.map((assay) => ({ ...assay, dillutions_text: Object.entries(assay.dillutions ?? {}).map(([key, value]) => `${key}=${value}`).join('\n'), reference_text: Object.entries(assay.reference ?? {}).map(([key, value]) => `${key}=${value}`).join('\n') })),
    });
}
function settingsText(value) {
    const result = {};
    value.split(/\r?\n/).map((line) => line.split('=')).filter((parts) => parts.length > 1 && parts[0].trim()).forEach(([key, ...rest]) => { result[key.trim()] = rest.join('=').trim(); });
    return result;
}
function addAssay(assay) { form.assays.push({ assay: assay.id, name: assay.name, dillutions_text: assay.dillution ? '-1=0.1\n-2=0.01\n-3=0.001\n-4=0.0001' : '', replicates: 0, reference_text: '', conf_trip: 0, reference_source: null }); }
function move(index, offset) { const target = index + offset; if (target < 0 || target >= form.assays.length) return; [form.assays[index], form.assays[target]] = [form.assays[target], form.assays[index]]; }
function submit() { store.save({ name: form.name, global: form.global, client: form.client?.id ?? null, portal_visible: form.portal_visible, lims_visible: form.lims_visible, assays: form.assays.map((assay) => ({ assay: assay.assay, dillutions: settingsText(assay.dillutions_text), replicates: Number(assay.replicates), reference: settingsText(assay.reference_text), conf_trip: Number(assay.conf_trip), reference_source: assay.reference_source || null })) }); }
function changeRevision(event) { window.location.href = props.endpoints.edit.replace('__ID__', event.target.value); }

watch(() => store.profile, applyProfile);
watch(() => form.global, (global) => { if (global) form.client = null; });
watch(() => [form.global, form.client?.id ?? null], ([global, clientId]) => store.loadReferenceSources(global ? null : clientId));
onMounted(async () => { store.configure(props.endpoints); await Promise.all([store.loadEditor(), store.loadProfiles()]); applyProfile(store.profile); });
</script>

<template>
    <div v-if="store.loading" class="sample-loading"><LoaderCircle class="spin" :size="20" />Profiel laden</div>
    <div v-else>
        <div v-if="store.message" class="notice success">{{ store.message }}</div><div v-if="store.error" class="notice error">{{ store.error }}</div>
        <div v-if="store.profile && !store.profile.is_tip" class="notice"><Info :size="16" /> Dit is een oudere revisie en kan niet worden aangepast.</div>
        <fieldset :disabled="store.profile && !store.profile.is_tip" class="profile-editor-grid">
            <div class="profile-editor-column">
                <section class="profile-panel"><h2><Info :size="16" />Algemene informatie</h2><div class="profile-panel-body form-grid">
                    <div class="field wide"><label for="profile-name">Naam</label><input id="profile-name" v-model="form.name" maxlength="128" required></div>
                    <div class="field"><label for="profile-scope">Profieltype</label><select id="profile-scope" v-model="form.global"><option :value="true">Globaal</option><option :value="false">Klantspecifiek</option></select></div>
                    <ResearchProfileClientPicker v-if="!form.global" v-model="form.client" :endpoint="endpoints.clients" />
                    <div class="check-grid wide"><label><input v-model="form.portal_visible" type="checkbox">Zichtbaar in client portal</label><label><input v-model="form.lims_visible" type="checkbox">Zichtbaar in profielselectie LIMS</label></div>
                </div></section>
                <section class="profile-panel"><h2><Search :size="16" />Analyses zoeken</h2><div class="profile-panel-body profile-filter"><input v-model="search" type="search" placeholder="Zoek op ID of analysenaam"><select v-model="matrix"><option value="">Alle matrices</option><option v-for="item in store.matrices" :key="item.id" :value="item.id">{{ item.name }}</option></select></div></section>
                <section class="profile-panel"><h2><Beaker :size="16" />Beschikbare analyses</h2><div class="profile-assay-list"><button v-for="assay in availableAssays" :key="assay.id" type="button" @click="addAssay(assay)"><span><strong>{{ assay.name }}</strong><small>ID {{ assay.id }}</small></span><Plus :size="16" /></button><p v-if="!availableAssays.length" class="empty-state">Geen analyses gevonden.</p></div></section>
            </div>
            <div class="profile-editor-column profile-editor-main">
                <section class="profile-panel"><h2><Beaker :size="16" />Geselecteerde analyses</h2><div class="table-scroll"><table class="data-table selected-assays"><thead><tr><th>Volg.</th><th>Analyse</th><th>Verdunningen</th><th>Replica's</th><th>Referentie</th><th>Referentiebron</th><th>Bevestig boven</th><th></th></tr></thead><tbody><tr v-for="(assay, index) in form.assays" :key="`${assay.assay}-${index}`"><td><button class="icon-button" type="button" title="Omhoog" @click="move(index, -1)"><ArrowUp :size="14" /></button><button class="icon-button" type="button" title="Omlaag" @click="move(index, 1)"><ArrowDown :size="14" /></button></td><td><strong>{{ assay.name }}</strong><small>ID {{ assay.assay }}</small></td><td><textarea v-model="assay.dillutions_text" rows="3"></textarea></td><td><input v-model.number="assay.replicates" type="number" min="0"></td><td><textarea v-model="assay.reference_text" rows="3"></textarea></td><td><select v-model="assay.reference_source"><option value="">Geen bron</option><option v-for="source in store.referenceSources" :key="source.id" :value="source.id">{{ source.name }}</option></select></td><td><input v-model.number="assay.conf_trip" type="number" min="0"></td><td><button class="icon-button danger" type="button" title="Verwijderen" @click="form.assays.splice(index, 1)"><Trash2 :size="15" /></button></td></tr><tr v-if="!form.assays.length"><td colspan="8" class="empty-state">Nog geen analyses geselecteerd.</td></tr></tbody></table></div></section>
                <section v-if="store.profile" class="profile-panel profile-save-panel"><h2><Save :size="16" />Opslaan en reviseren</h2><div class="profile-panel-body"><label class="revision-picker">Revisie<select :value="store.profile.id" @change="changeRevision"><option v-for="revision in store.revisions" :key="revision.id" :value="revision.id">{{ revision.label }}</option></select></label><button v-if="store.profile.is_tip" class="button primary" type="button" :disabled="store.saving || !form.name || (!form.global && !form.client)" @click="submit"><Save :size="16" />Nieuwe revisie opslaan</button></div></section>
                <section v-if="store.profile?.is_tip" class="profile-panel"><h2><ClipboardCopy :size="16" />Kopieren van ander profiel</h2><div class="profile-panel-body copy-profile-controls"><input v-model="sourceQuery" type="search" placeholder="Zoek profiel"><select v-model="sourceId"><option value="">Selecteer bronprofiel</option><option v-for="profile in copyProfiles" :key="profile.id" :value="profile.id">{{ profile.id }}: {{ profile.name }}</option></select><button class="button" type="button" :disabled="!sourceId || store.saving" @click="store.copy(sourceId)"><ClipboardCopy :size="16" />Kopieren</button></div></section>
            </div>
        </fieldset>
        <div v-if="!store.profile" class="form-actions"><button class="button primary" type="button" :disabled="store.saving || !form.name || (!form.global && !form.client)" @click="submit"><Save :size="16" />Profiel aanmaken</button><a class="button" :href="endpoints.index">Annuleren</a></div>
    </div>
</template>