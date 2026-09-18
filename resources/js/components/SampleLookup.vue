<script setup>
import { defineAsyncComponent, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { ArrowLeft, ArrowRight, Barcode, FileClock, Pencil, Search } from '@lucide/vue';
import { getEcho } from '../echo.js';
import { sfx } from '../sfx.js';
import { useSampleLookupStore } from '../stores/sampleLookupStore';
import SampleLookupResearch from './SampleLookupResearch.vue';
import SampleLookupResults from './SampleLookupResults.vue';
import SampleLookupPlaceholder from './SampleLookupPlaceholder.vue';
import ConfirmationDecisionDialog from './ConfirmationDecisionDialog.vue';
import ConfirmationDialog from './ConfirmationDialog.vue';
import { useConfirmationStore } from '../stores/confirmationStore';

const props = defineProps({ endpoints: { type: Object, required: true }, permissions: { type: Object, required: true } });
const store = useSampleLookupStore();
const confirmationStore = useConfirmationStore();
const EndResultDisplay = defineAsyncComponent(() => import('./EndResultDisplay.vue'));
const barcodeInput = ref(null);
let realtimeClient = null;
let subscribedSampleId = null;
let subscriptionRevision = 0;
const sampleChannelSettled = ref(false);
const sampleChannelFailed = ref(false);
store.endpoints = props.endpoints;
confirmationStore.configure(props.endpoints.confirmation);
const tabs = [{ id: 'general', label: 'Algemeen' }, { id: 'metadata', label: 'Metadata' }, { id: 'product', label: 'Productgroep / THT' }, { id: 'documents', label: 'Documenten' }];
function date(value) {
    if (!value || !Number(value)) return '-';
    return new Date(Number(value) * 1000).toLocaleString('nl-NL');
}
function display(value) {
    return value !== null && typeof value === 'object' ? JSON.stringify(value) : (value ?? '-');
}
async function setConfirmationDecision(decision) {
    if (!store.selectedId || store.readOnly || confirmationStore.pendingMutationCount) return;
    const analysisId = store.selectedId;
    const previousCalculation = store.calculation;
    store.markCalculationQueued(analysisId);
    const updated = await confirmationStore.setDecision(decision);
    if (!updated && store.selectedId === analysisId) store.applyCalculation(analysisId, previousCalculation);
}
function openConfirmation() {
    if (!store.selectedId || store.readOnly || confirmationStore.readOnly) return;
    confirmationStore.open(store.selectedId, 'global', 0);
}
function focusBarcodeInput() {
    barcodeInput.value?.focus();
    barcodeInput.value?.select();
}
async function scanBarcode() {
    const loaded = await store.lookup();

    if (loaded !== null) void sfx.play(loaded ? 'do' : 'warning').catch(() => {});
}
onMounted(() => {
    const barcode = new URLSearchParams(window.location.search).get('barcode');
    if (barcode) store.lookup(barcode);
});
watch(() => store.data?.sample.id, (sampleId) => {
    const revision = ++subscriptionRevision;
    sampleChannelSettled.value = false;
    sampleChannelFailed.value = false;
    if (subscribedSampleId !== null) realtimeClient?.leave(`samples.${subscribedSampleId}`);
    subscribedSampleId = sampleId ?? null;
    if (subscribedSampleId === null) return;

    realtimeClient = getEcho();
    confirmationStore.setSample(sampleId);
    realtimeClient.private(`samples.${subscribedSampleId}`)
        .subscribed(() => {
            if (revision === subscriptionRevision) sampleChannelSettled.value = true;
        })
        .listen('.analysis.result.calculated', (event) => {
            store.applyCalculationEvent(event);
            if (Number(event.analysis_id) !== Number(store.selectedId)) return;

            const calculatedConfirmation = event.calculation?.confirmation;
            if (calculatedConfirmation?.decision_required) {
                confirmationStore.queueDecisionPrompt({
                    analysis_id: Number(event.analysis_id),
                    mode: calculatedConfirmation.mode,
                });
            } else if (
                calculatedConfirmation
                && (
                    Number(calculatedConfirmation.decision) !== Number(confirmationStore.data?.decision)
                    || calculatedConfirmation.status !== confirmationStore.data?.status
                )
            ) {
                confirmationStore.load(Number(event.analysis_id));
            }
        })
        .listen('.analysis.result.calculation-failed', (event) => store.applyCalculationFailure(event))
        .listen('.analysis.confirmation.updated', (event) => confirmationStore.applyUpdatedEvent(event))
        .listen('.analysis.confirmation.decision-required', (event) => {
            if (Number(event.analysis_id) === Number(store.selectedId)) confirmationStore.queueDecisionPrompt(event);
        })
        .error(() => {
            if (revision !== subscriptionRevision) return;
            sampleChannelFailed.value = true;
            sampleChannelSettled.value = true;
            store.reportRealtimeError();
        });
}, { flush: 'sync' });
watch([() => store.selectedId, sampleChannelSettled], async ([analysisId, channelSettled]) => {
    if (!analysisId || !channelSettled) return;

    await store.loadResults(analysisId);
    if (sampleChannelFailed.value && store.selectedId === analysisId) store.reportRealtimeError();
}, { flush: 'sync' });
watch(() => store.selectedId, () => confirmationStore.reset(true), { flush: 'sync' });
watch(() => store.resultData?.confirmation, (confirmation) => {
    if (confirmation && store.selectedId) confirmationStore.hydrate(store.selectedId, confirmation);
});
watch(() => store.calculation?.confirmation, (confirmation) => {
    if (!confirmation?.decision_required || !store.selectedId) return;

    confirmationStore.queueDecisionPrompt({
        analysis_id: store.selectedId,
        mode: confirmation.mode,
    });
});
onBeforeUnmount(() => {
    subscriptionRevision++;
    if (subscribedSampleId !== null) realtimeClient?.leave(`samples.${subscribedSampleId}`);
    confirmationStore.reset();
});
</script>

<template>
    <div class="sample-lookup" :aria-busy="store.loading || store.saving">
        <p v-if="store.error" class="error-text" role="alert">{{ store.error }}</p>
        <p v-if="store.readOnly" class="lookup-warning">Project is vergrendeld of geautoriseerd. {{ store.data.project.lock_message }}</p>
        <div class="sample-create-grid">
            <div class="sample-create-column">
                <section class="sample-panel">
                    <h2><Barcode :size="16" />Monster opzoeken<span class="lookup-tools"><button class="icon-button" title="Vorig monster" :disabled="!store.data?.previous || store.saving" @click="store.lookup(store.data.previous)"><ArrowLeft :size="16" /></button><button class="icon-button" title="Volgend monster" :disabled="!store.data?.next || store.saving" @click="store.lookup(store.data.next)"><ArrowRight :size="16" /></button></span></h2>
                    <form class="sample-panel-body lookup-search" @submit.prevent="scanBarcode"><label class="sr-only" for="lookup-barcode">Barcode</label><div class="lookup-barcode-control"><Barcode :size="16" aria-hidden="true" /><input id="lookup-barcode" ref="barcodeInput" v-model="store.barcode" autofocus autocomplete="off" placeholder="Barcode" maxlength="32" :disabled="store.saving" @focus="$event.target.select()" @keydown.enter="$event.target.select()"></div><button class="button primary" title="Monster zoeken" :disabled="store.saving || !store.barcode.trim()"><Search :size="18" /></button></form>
                    <p v-if="store.loading" class="sample-panel-body" role="status">Monster laden...</p>
                </section>
                <section class="sample-panel">
                    <h2>Gescand monster<span class="lookup-tools"><button class="icon-button" title="Monsterrevisies" :disabled="!store.data" @click="store.placeholder('Monsterrevisies')"><FileClock :size="15" /></button><button class="icon-button" title="Monsterdetails wijzigen" :disabled="!store.data" @click="store.placeholder('Monsterdetails wijzigen')"><Pencil :size="15" /></button></span></h2>
                    <div class="sample-panel-body">
                        <p v-if="!store.data" class="empty-state">Geen monster geselecteerd.</p>
                        <template v-else>
                            <dl v-if="store.tab === 'general'" class="lookup-details">
                                <dt>Barcode</dt><dd><strong>{{ store.data.sample.barcode }}</strong></dd>
                                <dt>Volgnummer</dt><dd>{{ store.data.project_follow_number }}</dd>
                                <dt>Omschrijving</dt><dd>{{ store.data.sample.description }}</dd>
                                <dt>Klant</dt><dd>{{ store.data.client?.name ?? store.data.sample.client }}</dd>
                                <dt>Klantomschrijving</dt><dd>{{ store.data.sample.client_description }}</dd>
                                <dt>Aangemeld</dt><dd>{{ date(store.data.sample.date_registered) }}</dd>
                                <dt>Door</dt><dd>{{ store.data.registered_by ?? '-' }}</dd>
                                <dt>Monstername</dt><dd>{{ store.data.sampling_method ?? '-' }}</dd>
                                <dt>Type</dt><dd>{{ store.data.sample.sample_type }}</dd>
                                <dt>Ingezet</dt><dd>{{ date(store.data.sample.sample_innoculated) }}</dd>
                                <dt>Opgeslagen</dt><dd>{{ store.data.sample.stored_in || '-' }}</dd>
                                <dt>Afweegstation</dt><dd>{{ store.data.sample.diluted_at || '-' }}</dd>
                                <dt>Bron</dt><dd>{{ ({ 0: 'LIMS', 1: 'CSV import', 2: 'LIMS', 3: 'Portal' })[store.data.sample.source] ?? store.data.sample.source }}</dd>
                                <template v-for="field in store.data.sample_fields" :key="field.name"><dt>{{ field.label }}</dt><dd>{{ display(field.value) }}</dd></template>
                                <template v-for="field in store.data.sample_extra" :key="field.name"><dt>{{ field.label }}</dt><dd>{{ display(field.value) }}</dd></template>
                            </dl>
                            <SampleLookupPlaceholder v-else-if="store.tab === 'metadata'" feature="Portalmetadata" :details="{ portal_sample_id: store.data.sample.portal_sample_id, source: store.data.sample.source }" />
                            <SampleLookupPlaceholder v-else-if="store.tab === 'product'" feature="Productgroep / THT" :details="{ tht_code: store.data.sample.tht_code, portal_product_group_id: store.data.sample.portal_product_group_id }" />
                            <SampleLookupPlaceholder v-else feature="Documenten" />
                        </template>
                    </div>
                    <div class="lookup-tabs" role="tablist" aria-label="Monstergegevens"><button v-for="tab in tabs" :key="tab.id" role="tab" :aria-selected="store.tab === tab.id" @click="store.tab = tab.id">{{ tab.label }}</button></div>
                </section>
                <section class="sample-panel">
                    <h2>Project</h2>
                    <div class="sample-panel-body">
                        <template v-if="store.data?.project">
                            <dl class="lookup-details"><dt>Project</dt><dd>{{ store.data.project.project_name }}</dd><dt>Referentie</dt><dd>{{ store.data.project.reference || '-' }}</dd><dt>Datum</dt><dd>{{ date(store.data.project.project_date) }}</dd><dt>Status</dt><dd>{{ Number(store.data.project.auth_status) ? 'Geautoriseerd' : Number(store.data.project.is_ready) ? 'Gereed' : 'Open' }}</dd><template v-for="field in store.data.project_fields" :key="field.name"><dt>{{ field.label }}</dt><dd>{{ display(field.value) }}</dd></template><dt>Notities</dt><dd>{{ store.data.project.project_notes || '-' }}</dd></dl>
                            <label for="lookup-project-sample">Monsters in project</label><select id="lookup-project-sample" :value="store.data.sample.barcode" :disabled="store.saving" @change="store.lookup($event.target.value)"><option v-for="sample in store.data.project_samples" :key="sample.id" :value="sample.barcode">{{ sample.barcode }} · {{ sample.description }}</option></select>
                        </template><p v-else class="empty-state">Geen project geselecteerd.</p>
                    </div>
                </section>
            </div>
            <div class="sample-create-column">
                <SampleLookupResearch :permissions="permissions" />
                <section class="sample-panel"><h2>Voortgang</h2><div class="sample-panel-body"><progress :value="store.progress" max="100" :aria-label="`${store.progress}% gereed`"></progress><p>{{ store.progress }}% gereed</p><small v-if="store.data">Verwacht gereed: {{ date(store.data.sample.predicted_end) }}</small></div></section>
            </div>
            <div class="sample-create-column lookup-results-column">
                <section class="sample-panel"><h2>Resultaat uitgedrukt in</h2><div class="sample-panel-body"><EndResultDisplay :calculation="store.calculation" :confirmation="confirmationStore.data" :confirmation-busy="confirmationStore.pendingMutationCount > 0" :confirmation-error="confirmationStore.error" :can-reset-confirmation="permissions.resetConfirmation" :read-only="store.readOnly || confirmationStore.readOnly" :loading="store.calculationLoading" :error="store.calculationError" empty-text="Selecteer een analyse om het eindresultaat te bekijken." @confirmation-decision="setConfirmationDecision" @open-confirmation="openConfirmation" /></div></section>
                <section class="sample-panel"><h2>Laboratoriumresultaten<span class="lookup-tools"><button class="icon-button" title="Resultaatrevisies" :disabled="!store.selected" @click="store.placeholder('Resultaatrevisies')"><FileClock :size="15" /></button><button class="icon-button" title="Verdunningen wijzigen" :disabled="!store.selected" @click="store.placeholder('Verdunningen wijzigen')"><Pencil :size="15" /></button></span></h2><div class="sample-panel-body"><SampleLookupResults @finished-entry="focusBarcodeInput" /></div></section>
                <section class="sample-panel"><h2>Monster notities<span class="lookup-tools"><button class="icon-button" title="Notities wijzigen" :disabled="!store.data" @click="store.placeholder('Monsternotities wijzigen')"><Pencil :size="15" /></button></span></h2><div class="sample-panel-body lookup-note">{{ store.data?.sample.sample_note || 'Geen notities.' }}</div></section>
                <section v-if="store.debug" class="sample-panel" aria-live="polite"><h2>{{ store.debug.feature }}</h2><div class="sample-panel-body"><SampleLookupPlaceholder :feature="store.debug.feature" :details="store.debug" /></div></section>
            </div>
        </div>
        <ConfirmationDialog />
        <ConfirmationDecisionDialog />
    </div>
</template>

<style>
.lookup-tools { display:flex; gap:3px; margin-left:auto; flex-wrap:wrap; }
.lookup-search { display:flex; gap:8px; }
.lookup-barcode-control { position:relative; display:flex; align-items:center; flex:1; min-width:0; }
.lookup-barcode-control > svg { position:absolute; left:10px; z-index:1; color:var(--muted); pointer-events:none; }
.lookup-barcode-control input { width:100%; min-width:0; min-height:35px; border:1px solid #9eabb2; border-radius:2px; background:var(--surface); color:var(--ink); padding:8px 10px 8px 34px; font:inherit; }
.lookup-details { display:grid; grid-template-columns:minmax(80px, 1fr) minmax(0, 2fr); gap:8px 12px; margin:0 0 16px; font-size:12px; }
.lookup-details dt { color:var(--muted); }
.lookup-details dd { margin:0; white-space:pre-wrap; overflow-wrap:anywhere; }
.lookup-tabs { display:flex; flex-wrap:wrap; border-top:1px solid var(--line); }
.lookup-tabs button { flex:1; padding:9px 6px; border:0; background:var(--surface-alt); color:var(--ink); font:inherit; font-size:11px; cursor:pointer; }
.lookup-tabs button[aria-selected=true] { box-shadow:inset 0 -2px var(--accent); color:var(--accent); background:var(--accent-faint); }
.lookup-analysis-list h3 { font-size:12px; margin:12px 0 6px; overflow-wrap:anywhere; }
.lookup-analysis-list section > button { display:flex; justify-content:space-between; gap:10px; width:100%; min-height:54px; padding:8px; border:0; border-bottom:1px solid var(--line); background:var(--surface); text-align:left; color:var(--ink); cursor:pointer; }
.lookup-analysis-list section > button[aria-pressed=true] { background:var(--accent-faint); box-shadow:inset 3px 0 var(--accent); }
.lookup-analysis-list strong, .lookup-analysis-list small { display:block; overflow-wrap:anywhere; }
.lookup-status { color:#9a5500; font-size:11px; }
.lookup-status.ready { color:#247448; }
.lookup-placeholder p { color:var(--muted); font-size:12px; }
.lookup-placeholder pre { max-height:260px; overflow:auto; white-space:pre-wrap; overflow-wrap:anywhere; font-size:11px; line-height:1.6; }
.sample-lookup progress { width:100%; height:15px; accent-color:var(--accent); }
.sample-lookup select { width:100%; min-width:0; margin-top:6px; }
.lookup-note { white-space:pre-wrap; overflow-wrap:anywhere; }
.lookup-warning { padding:10px; background:#fff3cf; color:#714d00; }
.lookup-add-modal { width:min(760px, calc(100vw - 24px)); }
.lookup-add-modal fieldset { margin:0; border:0; min-width:0; display:grid; gap:16px; }
.lookup-confirmations { margin-top:12px; }
.sample-lookup h3 { font-size:14px; overflow-wrap:anywhere; }
@media (min-width:1101px) { .lookup-results-column { position:sticky; top:0; } }
@media (max-width:1100px) { .sample-lookup .sample-create-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media (max-width:700px) { .sample-lookup .sample-create-grid { grid-template-columns:minmax(0,1fr); } }
</style>