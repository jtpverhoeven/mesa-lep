<script setup>
import { computed, ref } from 'vue';
import { ArrowDown, ArrowUp, Barcode, Plus, Star, Trash2, X } from '@lucide/vue';
import Dialog from 'openvue/dialog';
import { useSampleLookupStore } from '../stores/sampleLookupStore';
import { useSampleResearchStore } from '../stores/sampleResearchStore';
import SampleResearchSelector from './SampleResearchSelector.vue';
import SelectedSampleResearch from './SelectedSampleResearch.vue';

defineProps({ permissions: { type: Object, required: true } });
const store = useSampleLookupStore();
const research = useSampleResearchStore();
const selector = ref(null);
const removing = ref(null);
const addDialogPassThrough = {
    mask: { class: 'p-3 bg-[rgba(20,35,45,.45)]' },
    root: { class: 'w-[min(760px,calc(100vw-24px))] max-h-[calc(100vh-24px)] overflow-auto border border-[#8e9ba2] bg-[var(--surface)] shadow-[0_18px_45px_rgba(20,35,45,.3)]', 'aria-labelledby': 'lookup-add-title' },
    header: { class: 'flex min-h-[56px] items-center justify-between gap-3 border-b border-[var(--line)] bg-[var(--surface-alt)] px-3 py-[9px]' },
    headerActions: { class: 'hidden' },
    content: { class: 'p-0' },
    footer: { class: 'flex flex-wrap justify-end gap-2 border-t border-[var(--line)] bg-[var(--surface-alt)] px-3 py-2.5' },
};
const removeDialogPassThrough = {
    mask: { class: 'p-[18px] bg-[rgba(20,35,45,.45)]' },
    root: { class: 'w-[min(620px,100%)] max-h-[calc(100vh-36px)] overflow-auto border border-[#8e9ba2] bg-[var(--surface)] shadow-[0_18px_45px_rgba(20,35,45,.3)]', role: 'alertdialog', 'aria-labelledby': 'lookup-remove-title' },
    header: { class: 'flex min-h-[56px] items-center justify-between gap-3 border-b border-[var(--line)] bg-[var(--surface-alt)] px-3 py-[9px]' },
    content: { class: 'p-0' },
    footer: { class: 'flex flex-wrap justify-end gap-2 border-t border-[var(--line)] bg-[var(--surface-alt)] px-3 py-2.5' },
};
const groups = computed(() => {
    const groups = [];
    for (const analysis of store.data?.analyses ?? []) {
        const last = groups.at(-1);
        if (last?.key === `${analysis.profile_group}-${analysis.profile}`) last.analyses.push(analysis);
        else groups.push({ key: `${analysis.profile_group}-${analysis.profile}`, name: analysis.profile_name, analyses: [analysis] });
    }
    return groups;
});
</script>

<template>
    <section class="sample-panel">
        <h2>Onderzoek <span class="lookup-tools">
            <button v-if="permissions.add" class="icon-button" title="Onderzoek toevoegen" :disabled="!store.data || store.saving || store.readOnly" @click="store.openResearch()"><Plus :size="15" /></button>
            <template v-if="permissions.edit">
                <button class="icon-button" title="Analyse omhoog" :disabled="!store.selected || store.saving || store.readOnly || store.selectedId === store.data?.analyses[0]?.id" @click="store.move(-1)"><ArrowUp :size="15" /></button>
                <button class="icon-button" title="Analyse omlaag" :disabled="!store.selected || store.saving || store.readOnly || store.selectedId === store.data?.analyses.at(-1)?.id" @click="store.move(1)"><ArrowDown :size="15" /></button>
                <button class="icon-button danger" title="Analyse verwijderen" :disabled="!store.selected || store.saving || store.readOnly" @click="removing = store.selected"><Trash2 :size="15" /></button>
                <button class="icon-button" title="Referentiewaarden wijzigen" :disabled="!store.selected" @click="store.placeholder('Referentiewaarden wijzigen')"><Star :size="15" /></button>
            </template>
        </span></h2>
        <div class="sample-panel-body lookup-analysis-list">
            <p v-if="!groups.length" class="empty-state">Geen onderzoek aanwezig.</p>
            <section v-for="(group, index) in groups" :key="`${group.key}-${index}`">
                <h3>{{ group.name }}</h3>
                <button v-for="analysis in group.analyses" :key="analysis.id" type="button" :aria-pressed="store.selectedId === analysis.id" @click="store.selectedId = analysis.id; store.debug = null">
                    <span class="lookup-research-follow">{{ analysis.follow_number }}.</span>
                    <span class="lookup-research-description"><strong>{{ analysis.name }}</strong><span class="lookup-research-barcode"><Barcode :size="14" aria-hidden="true" /><span>{{ store.data.sample.barcode }}</span></span></span>
                    <span class="lookup-status" :class="{ ready: analysis.is_ready }">{{ analysis.is_ready ? 'Gereed' : 'Open' }}<small v-if="analysis.conf_requested">{{ Number(analysis.conf_requested) === 2 ? 'Bevestiging afgewezen' : 'Bevestiging gevraagd' }}</small></span>
                </button>
            </section>
        </div>
    </section>
    <Dialog v-model:visible="store.adding" modal :draggable="false" :dismissable-mask="false" :close-on-escape="!store.saving" :closable="false" :block-scroll="true" :unstyled="true" :pt="addDialogPassThrough">
        <template #header><h3 id="lookup-add-title" class="m-0 text-[15px]">Onderzoek toevoegen · {{ store.data?.sample.barcode }}</h3><button class="icon-button" type="button" title="Sluiten" :disabled="store.saving" @click="store.adding = false"><X :size="18" /></button></template>
        <fieldset class="grid gap-4 p-[14px]" :disabled="store.saving">
            <SampleResearchSelector ref="selector" />
            <SelectedSampleResearch @edit-profile-assay="(entry, assay) => selector.editProfileAssay(entry, assay)" @edit-roaming="(entry) => selector.editRoaming(entry)" />
        </fieldset>
        <p v-if="store.error" role="alert" class="px-[14px] pb-[14px] text-[11px] text-[#903e32]">{{ store.error }}</p>
        <template #footer><button class="button" type="button" :disabled="store.saving" @click="store.adding = false">Annuleren</button><button class="button primary" type="button" :disabled="store.saving || research.loading || !research.selected.length || !!research.pendingConflict" @click="store.mutate({ operation: 'add', analyses: research.payload })"><Plus :size="16" />{{ store.saving ? 'Opslaan...' : 'Toevoegen' }}</button></template>
    </Dialog>
    <Dialog :visible="removing !== null" modal :draggable="false" :dismissable-mask="false" :close-on-escape="false" :closable="false" :block-scroll="true" :unstyled="true" :pt="removeDialogPassThrough" @update:visible="removing = null">
        <template #header><h3 id="lookup-remove-title" class="m-0 text-[15px]">Analyse verwijderen</h3></template>
        <div class="p-[14px]">{{ removing?.name }} (SAID {{ removing?.id }}) verwijderen?</div>
        <template #footer><button class="button" type="button" @click="removing = null">Annuleren</button><button class="button danger" type="button" @click="store.mutate({ operation: 'remove', analysis_id: removing.id }); removing = null"><Trash2 :size="15" />Verwijderen</button></template>
    </Dialog>
</template>

<style scoped>
.lookup-research-follow { flex:none; min-width:2ch; align-self:center; font-weight:700; text-align:right; }
.lookup-research-description { flex:1; min-width:0; display:grid; gap:6px; }
.lookup-research-barcode { display:flex; align-items:center; gap:4px; color:var(--accent); font-size:12px; overflow-wrap:anywhere; }
.lookup-research-barcode svg { flex:none; color:var(--muted); }
.lookup-research-barcode span { min-width:0; }
</style>