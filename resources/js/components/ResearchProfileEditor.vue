<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { ArrowDown, ArrowUp, Beaker, ClipboardCopy, Info, LoaderCircle, Pencil, Plus, Save, Search, Trash2 } from '@lucide/vue';
import Button from 'openvue/button';
import InputNumber from 'openvue/inputnumber';
import InputText from 'openvue/inputtext';
import Select from 'openvue/select';
import Textarea from 'openvue/textarea';
import AppDialog from './AppDialog.vue';
import ResearchProfileClientPicker from './ResearchProfileClientPicker.vue';
import { useResearchProfileStore } from '../stores/researchProfileStore';
import { createAssayDraft, referenceFields, referenceSourceProblem, referenceValues as references, validateAssayDraft } from '../researchProfileAssays';

const props = defineProps({ endpoints: { type: Object, required: true } });
const store = useResearchProfileStore();
const form = reactive({ name: '', global: true, client: null, portal_visible: false, lims_visible: true, assays: [] });
const search = ref('');
const matrix = ref('');
const sourceId = ref('');
const sourceQuery = ref('');
const draft = ref(null);
const editingIndex = ref(null);
const draftErrors = ref({});
const readOnly = computed(() => Boolean(store.profile && !store.profile.is_tip));
const canSave = computed(() => !readOnly.value && !store.saving && !store.referenceSourcesLoading && !store.referenceSourcesError
    && Boolean(form.name.trim()) && (form.global || form.client) && !form.assays.some(referenceProblem));
const draftFields = computed(() => referenceFields(draft.value));

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
        assays: profile.assays.map((assay) => ({
            ...assay,
            reference: Object.fromEntries(Object.entries(assay.reference ?? {}).map(([key, value]) => [key, value == null ? '' : String(value)])),
            dillutions_text: Object.entries(assay.dillutions ?? {}).map(([key, value]) => `${key}=${value}`).join('\n'),
        })),
    });
    draft.value = null;
}
function settingsText(value) {
    const result = {};
    value.split(/\r?\n/).map((line) => line.split('=')).filter((parts) => parts.length > 1 && parts[0].trim()).forEach(([key, ...rest]) => { result[key.trim()] = rest.join('=').trim(); });
    return result;
}
function referenceProblem(assay) {
    return referenceSourceProblem(assay, store.referenceSources, !store.referenceSourcesLoading && !store.referenceSourcesError);
}
function sourceName(assay) {
    return store.referenceSources.find((source) => Number(source.id) === Number(assay.reference_source))?.name
        ?? assay.reference_source_record?.name ?? (assay.reference_source ? 'Niet beschikbare bron' : 'Geen bron');
}
function editAssay(assay, index = null) {
    if (readOnly.value) return;
    editingIndex.value = index;
    draftErrors.value = {};
    draft.value = createAssayDraft(assay);
}
function addAssay(assay) {
    editAssay({
        assay: assay.id, name: assay.name, configuration: assay.configuration,
        dillutions_text: assay.dillution ? '-1=0.1\n-2=0.01\n-3=0.001\n-4=0.0001' : '',
        replicates: 0, reference: {}, conf_trip: 0, reference_source: null,
    });
}
function applyAssay() {
    if (readOnly.value || !draft.value || store.referenceSourcesLoading || store.referenceSourcesError) return;
    const errors = validateAssayDraft(draft.value, referenceProblem(draft.value));
    draftErrors.value = errors;
    if (Object.keys(errors).length) return;
    const selected = { ...draft.value, reference: references(draft.value) };
    if (editingIndex.value === null) form.assays.push(selected);
    else form.assays.splice(editingIndex.value, 1, selected);
    draft.value = null;
}
function move(index, offset) { const target = index + offset; if (target < 0 || target >= form.assays.length) return; [form.assays[index], form.assays[target]] = [form.assays[target], form.assays[index]]; }
function submit() {
    if (!canSave.value) return;
    store.save({ name: form.name, global: form.global, client: form.client?.id ?? null, portal_visible: form.portal_visible, lims_visible: form.lims_visible, assays: form.assays.map((assay) => ({ assay: assay.assay, dillutions: settingsText(assay.dillutions_text), replicates: Number(assay.replicates), reference: references(assay), conf_trip: Number(assay.conf_trip), reference_source: assay.reference_source || null })) });
}
function changeRevision(event) { window.location.href = props.endpoints.edit.replace('__ID__', event.target.value); }

watch(() => store.profile, applyProfile);
watch(() => form.global, (global) => { if (global) form.client = null; });
watch(() => [form.global, form.client?.id ?? null], ([global, clientId]) => store.loadReferenceSources(global ? null : clientId));
onMounted(async () => { store.configure(props.endpoints); await Promise.all([store.loadEditor(), store.loadProfiles()]); });
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
                <section class="profile-panel">
                    <h2><Beaker :size="16" />Geselecteerde analyses <span class="ml-auto text-muted">{{ form.assays.length }}</span></h2>
                    <div class="profile-panel-body border-b border-line text-muted">Bekijk de instellingen hieronder. Kies Bewerken om een analyse aan te passen. Sla daarna het profiel op.</div>
                    <div v-if="store.referenceSourcesError" class="notice error" role="alert">{{ store.referenceSourcesError }} <Button label="Opnieuw proberen" severity="secondary" @click="store.loadReferenceSources(form.global ? null : form.client?.id)" /></div>
                    <ol class="m-0 grid list-none gap-0 p-0">
                        <li v-for="(assay, index) in form.assays" :key="`${assay.assay}-${index}`" class="grid min-w-0 gap-3 border-b border-line p-3 last:border-b-0">
                            <div class="flex min-w-0 flex-wrap items-center gap-2">
                                <span class="text-muted">{{ index + 1 }}.</span>
                                <strong class="min-w-0 flex-1 wrap-anywhere">{{ assay.name }}</strong>
                                <span class="text-xs text-muted">ID {{ assay.assay }}</span>
                                <div class="flex flex-wrap gap-1">
                                    <Button type="button" severity="secondary" variant="text" :aria-label="`${assay.name} omhoog`" title="Omhoog" :disabled="readOnly || index === 0" @click="move(index, -1)"><ArrowUp :size="14" /></Button>
                                    <Button type="button" severity="secondary" variant="text" :aria-label="`${assay.name} omlaag`" title="Omlaag" :disabled="readOnly || index === form.assays.length - 1" @click="move(index, 1)"><ArrowDown :size="14" /></Button>
                                    <Button type="button" severity="secondary" :disabled="readOnly" :aria-label="`${assay.name} bewerken`" @click="editAssay(assay, index)"><Pencil :size="14" />Bewerken</Button>
                                    <Button type="button" severity="danger" variant="text" :disabled="readOnly" :aria-label="`${assay.name} verwijderen`" title="Verwijderen" @click="form.assays.splice(index, 1)"><Trash2 :size="15" /></Button>
                                </div>
                            </div>
                            <dl class="m-0 grid min-w-0 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                <div><dt class="text-xs text-muted">Verdunningen</dt><dd class="m-0 whitespace-pre-line wrap-anywhere">{{ assay.dillutions_text || 'Niet van toepassing' }}</dd></div>
                                <div><dt class="text-xs text-muted">Replica's</dt><dd class="m-0">{{ assay.configuration?.replicates || assay.replicates ? assay.replicates : 'Niet van toepassing' }}</dd></div>
                                <div><dt class="text-xs text-muted">Bevestigen boven</dt><dd class="m-0">{{ assay.conf_trip || 'Analyse-instelling (0)' }}</dd></div>
                                <div v-for="field in referenceFields(assay)" :key="field.key"><dt class="text-xs text-muted">Referentie · {{ field.label }}</dt><dd class="m-0 wrap-anywhere">{{ assay.reference[field.key] || 'Niet ingesteld' }}</dd></div>
                                <div><dt class="text-xs text-muted">Referentiebron</dt><dd class="m-0 wrap-anywhere">{{ sourceName(assay) }}</dd></div>
                            </dl>
                            <p v-if="!readOnly && referenceProblem(assay)" class="m-0 text-danger" role="alert">{{ referenceProblem(assay) }} Kies Bewerken om dit te herstellen.</p>
                        </li>
                    </ol>
                    <p v-if="!form.assays.length" class="empty-state">Nog geen analyses geselecteerd. Kies links een analyse om deze in te stellen.</p>
                </section>
                <section v-if="store.profile" class="profile-panel profile-save-panel"><h2><Save :size="16" />Opslaan en reviseren</h2><div class="profile-panel-body"><label class="revision-picker">Revisie<select :value="store.profile.id" @change="changeRevision"><option v-for="revision in store.revisions" :key="revision.id" :value="revision.id">{{ revision.label }}</option></select></label><Button v-if="store.profile.is_tip" type="button" :loading="store.saving" :disabled="!canSave" @click="submit"><Save :size="16" />Nieuwe revisie opslaan</Button></div></section>
                <section v-if="store.profile?.is_tip" class="profile-panel"><h2><ClipboardCopy :size="16" />Kopieren van ander profiel</h2><div class="profile-panel-body copy-profile-controls"><input v-model="sourceQuery" type="search" placeholder="Zoek profiel"><select v-model="sourceId"><option value="">Selecteer bronprofiel</option><option v-for="profile in copyProfiles" :key="profile.id" :value="profile.id">{{ profile.id }}: {{ profile.name }}</option></select><button class="button" type="button" :disabled="!sourceId || store.saving" @click="store.copy(sourceId)"><ClipboardCopy :size="16" />Kopieren</button></div></section>
            </div>
        </fieldset>
        <div v-if="!store.profile" class="form-actions"><Button type="button" :loading="store.saving" :disabled="!canSave" @click="submit"><Save :size="16" />Profiel aanmaken</Button><a class="button" :href="endpoints.index">Annuleren</a></div>
        <AppDialog :visible="Boolean(draft)" :title="editingIndex === null ? 'Analyse toevoegen' : 'Analyse bewerken'" width="640px" :dismissable-mask="false" @update:visible="draft = null">
            <form v-if="draft" id="profile-assay-form" class="grid gap-5" @submit.prevent="applyAssay">
                <div><strong>{{ draft.name }}</strong><div class="text-xs text-muted">ID {{ draft.assay }} · Wijzigingen worden pas definitief na het opslaan van het profiel.</div></div>
                <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                    <div v-if="draft.configuration?.dillution || draft.dillutions_text" class="field">
                        <label for="assay-dillutions">Verdunningen</label>
                        <Textarea id="assay-dillutions" v-model="draft.dillutions_text" rows="4" :invalid="Boolean(draftErrors.dillutions)" aria-describedby="assay-dillutions-help" />
                        <small id="assay-dillutions-help" class="text-muted">Een verdunning per regel, bijvoorbeeld -1=0.1.</small>
                        <small v-if="draftErrors.dillutions" class="text-danger" role="alert">{{ draftErrors.dillutions }}</small>
                    </div>
                    <div class="grid content-start gap-4">
                        <div v-if="draft.configuration?.replicates || draft.replicates" class="field">
                            <label for="assay-replicates">Replica's</label>
                            <InputNumber v-model="draft.replicates" input-id="assay-replicates" :min="0" :max-fraction-digits="0" :use-grouping="false" :invalid="Boolean(draftErrors.replicates)" />
                            <small v-if="draftErrors.replicates" class="text-danger" role="alert">{{ draftErrors.replicates }}</small>
                        </div>
                        <div class="field">
                            <label for="assay-conf-trip">Bevestigen boven</label>
                            <InputNumber v-model="draft.conf_trip" input-id="assay-conf-trip" :min="0" :max-fraction-digits="0" :use-grouping="false" :invalid="Boolean(draftErrors.conf_trip)" aria-describedby="assay-conf-trip-help" />
                            <small id="assay-conf-trip-help" class="text-muted">0 = analyse-instelling; hoger dan 0 = bevestigen boven deze waarde, anders automatisch niet.</small>
                            <small v-if="draftErrors.conf_trip" class="text-danger" role="alert">{{ draftErrors.conf_trip }}</small>
                        </div>
                    </div>
                </div>
                <section class="grid min-w-0 gap-4 border-t border-line pt-4" aria-labelledby="assay-reference-heading">
                    <div><h3 id="assay-reference-heading" class="m-0 font-semibold">Referentiewaarden</h3><p class="mb-0 mt-1 text-muted">Laat leeg als er geen referentie geldt. Bij een ingevulde waarde, ook 0, is een referentiebron verplicht.</p></div>
                    <p v-if="!draft.configuration?.reference_outputs" class="m-0 text-danger" role="alert">De uitvoervelden van deze workflow zijn nog niet geconfigureerd. Bestaande referenties blijven behouden; laat de workflow configureren om nieuwe referenties in te stellen.</p>
                    <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                        <div v-for="(field, index) in draftFields" :key="field.key" class="field">
                            <label :for="`assay-reference-${index}`">{{ field.label }}</label>
                            <InputText :id="`assay-reference-${index}`" v-model="draft.reference[field.key]" placeholder="Geen referentie" />
                            <small v-if="field.label !== field.name" class="text-muted">{{ field.name }}</small>
                        </div>
                    </div>
                    <div class="field">
                        <label id="assay-reference-source-label" for="assay-reference-source">Referentiebron <span v-if="Object.keys(references(draft)).length" class="text-danger">(verplicht)</span></label>
                        <Select v-model="draft.reference_source" input-id="assay-reference-source" aria-labelledby="assay-reference-source-label" :options="store.referenceSources" option-label="name" option-value="id" filter show-clear placeholder="Selecteer een referentiebron" :loading="store.referenceSourcesLoading" :disabled="store.referenceSourcesLoading || Boolean(store.referenceSourcesError)" :invalid="Boolean(referenceProblem(draft))" :aria-required="Boolean(Object.keys(references(draft)).length)" aria-describedby="assay-source-help" />
                        <small id="assay-source-help" :class="referenceProblem(draft) ? 'text-danger' : 'text-muted'" :role="referenceProblem(draft) ? 'alert' : undefined">{{ referenceProblem(draft) || 'Alleen globale bronnen en bronnen van de geselecteerde klant zijn beschikbaar.' }}</small>
                        <small v-if="store.referenceSourcesError" class="text-danger" role="alert">{{ store.referenceSourcesError }}</small>
                    </div>
                </section>
            </form>
            <template #footer>
                <Button type="button" label="Annuleren" severity="secondary" @click="draft = null" />
                <Button type="submit" form="profile-assay-form" :label="editingIndex === null ? 'Analyse toevoegen' : 'Wijzigingen toepassen'" :disabled="readOnly || store.referenceSourcesLoading || Boolean(store.referenceSourcesError)" />
            </template>
        </AppDialog>
    </div>
</template>