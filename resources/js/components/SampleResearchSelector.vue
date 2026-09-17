<script setup>
import { computed, ref } from 'vue';
import { AlertTriangle, FlaskConical, LoaderCircle, Plus, Search, UsersRound, X } from '@lucide/vue';
import Dialog from 'openvue/dialog';
import { useSampleResearchStore } from '../stores/sampleResearchStore';
import RoamingAnalysisDialog from './RoamingAnalysisDialog.vue';

const store = useSampleResearchStore();
const tab = ref('profiles');
const query = ref('');
const matrix = ref('');
const accreditation = ref('all');
const roaming = ref({ open: false, assay: null, defaults: null, editSource: null, editKey: null });
const conflictDialogPassThrough = {
    mask: { class: 'p-[20px] bg-[rgba(20,35,45,.45)]' },
    root: { class: 'w-[min(520px,100%)] max-h-[calc(100vh-40px)] overflow-auto border border-[#8e9ba2] bg-[var(--surface)] shadow-[0_18px_45px_rgba(20,35,45,.3)]', role: 'alertdialog', 'aria-labelledby': 'conflict-title' },
    header: { class: 'flex min-h-[56px] items-center justify-between gap-3 border-b border-[var(--line)] bg-[var(--surface-alt)] px-3 py-[9px]' },
    headerActions: { class: 'hidden' },
    content: { class: 'p-0' },
    footer: { class: 'flex flex-wrap justify-end gap-2 border-t border-[var(--line)] bg-[var(--surface-alt)] px-3 py-2.5' },
};

const filteredProfiles = computed(() => store.profiles.filter((profile) => !query.value || profile.name.toLowerCase().includes(query.value.toLowerCase())));
const filteredAssays = computed(() => store.assays.filter((assay) => {
    if (query.value && !assay.name.toLowerCase().includes(query.value.toLowerCase()) && !String(assay.id).includes(query.value)) return false;
    if (matrix.value && !assay.matrices.map(Number).includes(Number(matrix.value))) return false;
    if (accreditation.value === 'q') return assay.accreditation === 'q';
    if (accreditation.value === 'nq') return assay.accreditation !== 'q';
    return true;
}));

function defaultSettings(assay) {
    return { dillutions: assay.dillution ? { '-1': '0.1', '-2': '0.01', '-3': '0.001', '-4': '0.0001' } : { 0: 1 }, replicates: 0, reference: {}, reference_source: null, reference_scope: '' };
}

function requestAssay(assay) {
    if (Number(assay.type) === 2) {
        store.addRoaming(assay, defaultSettings(assay));
        return;
    }
    roaming.value = { open: true, assay, defaults: null, editSource: null, editKey: null };
}

function editProfileAssay(entry, assayProfile) {
    const assay = store.assayById(assayProfile.assay);
    if (!assay) return;
    roaming.value = { open: true, assay, defaults: assayProfile, editSource: { entryKey: entry.key, assayProfileId: assayProfile.id }, editKey: null };
}

function editRoaming(entry) {
    roaming.value = { open: true, assay: entry.assay, defaults: entry.settings, editSource: null, editKey: entry.key };
}

function saveRoaming(settings) {
    const dialog = roaming.value;
    const saved = dialog.editKey ? (store.updateRoaming(dialog.editKey, settings), true) : store.addRoaming(dialog.assay, settings, dialog.editSource);
    if (saved) roaming.value = { open: false, assay: null, defaults: null, editSource: null, editKey: null };
}

defineExpose({ editProfileAssay, editRoaming });
</script>

<template>
    <div class="research-selector">
        <div v-if="!store.clientId" class="research-empty"><UsersRound :size="22" /><p>Selecteer eerst een klant.</p></div>
        <div v-else-if="store.loading" class="research-empty"><LoaderCircle class="spin" :size="20" /><p>Onderzoek laden</p></div>
        <template v-else>
            <div class="research-tabs" role="tablist" aria-label="Onderzoekstype"><button type="button" role="tab" :aria-selected="tab === 'profiles'" @click="tab = 'profiles'; query = ''">Profielen</button><button type="button" role="tab" :aria-selected="tab === 'assays'" @click="tab = 'assays'; query = ''">Analyses</button></div>
            <div v-if="tab === 'assays'" class="field wide"><label for="assay-matrix">Matrix</label><select id="assay-matrix" v-model="matrix"><option value="">Alle matrices</option><option v-for="item in store.matrices" :key="item.id" :value="item.id">{{ item.name }}</option></select></div>
            <div v-if="tab === 'assays'" class="research-segments" aria-label="Accreditatie"><button type="button" :class="{ active: accreditation === 'all' }" @click="accreditation = 'all'">Alles</button><button type="button" :class="{ active: accreditation === 'q' }" @click="accreditation = 'q'">Q</button><button type="button" :class="{ active: accreditation === 'nq' }" @click="accreditation = 'nq'">Niet-Q</button></div>
            <label class="assay-search"><Search :size="16" aria-hidden="true" /><span class="sr-only">Onderzoek zoeken</span><input v-model="query" type="search" :placeholder="tab === 'profiles' ? 'Profiel zoeken' : 'Analyse zoeken'"></label>
            <div class="research-option-list">
                <template v-if="tab === 'profiles'"><button v-for="profile in filteredProfiles" :key="profile.id" type="button" @click="store.requestProfile(profile)"><span><strong>{{ profile.name }}</strong><small>{{ profile.global ? 'Globaal profiel' : 'Klantprofiel' }} · {{ profile.assays.length }} analyses</small></span><Plus :size="16" /></button></template>
                <template v-else><button v-for="assay in filteredAssays" :key="assay.id" type="button" @click="requestAssay(assay)"><span><strong>{{ assay.name }}</strong><small>ID {{ assay.id }} · {{ assay.accreditation === 'q' ? 'Q' : 'Niet-Q' }}</small></span><Plus :size="16" /></button></template>
                <p v-if="(tab === 'profiles' ? filteredProfiles : filteredAssays).length === 0" class="empty-state">Geen onderzoek gevonden.</p>
            </div>
        </template>
        <p v-if="store.error" class="error-text research-error">{{ store.error }}</p>
    </div>

    <Dialog :visible="store.pendingConflict !== null" modal :draggable="false" :dismissable-mask="false" :close-on-escape="false" :closable="false" :block-scroll="true" :unstyled="true" :pt="conflictDialogPassThrough" @update:visible="store.pendingConflict = null">
        <template #header>
            <div><small class="block text-[10px] text-[var(--muted)]">Dubbele analyse</small><h3 id="conflict-title" class="m-0 text-[15px]">Keuze vereist</h3></div>
            <AlertTriangle :size="20" />
        </template>
        <div class="p-[14px]"><p><strong>{{ store.pendingConflict?.profile.name }}</strong> bevat onderzoek dat al geselecteerd is:</p><ul class="m-[10px_0_0] list-disc pl-5"><li v-for="assay in store.pendingConflict?.conflicts ?? []" :key="assay.id">{{ assay.name }}</li></ul></div>
        <template #footer><button class="button" type="button" @click="store.resolveConflict('cancel')"><X :size="15" />Annuleren</button><button class="button" type="button" @click="store.resolveConflict('exclude')">Dubbele overslaan</button><button class="button primary" type="button" @click="store.resolveConflict('replace')">Bestaande vervangen</button></template>
    </Dialog>

    <RoamingAnalysisDialog :open="roaming.open" :assay="roaming.assay" :defaults="roaming.defaults" :reference-sources="store.referenceSources" @cancel="roaming.open = false" @save="saveRoaming" />
</template>