<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { ArrowDown, ArrowUp, GripVertical } from '@lucide/vue';
import Button from 'openvue/button';
import Tab from 'openvue/tab';
import TabList from 'openvue/tablist';
import TabPanel from 'openvue/tabpanel';
import TabPanels from 'openvue/tabpanels';
import Tabs from 'openvue/tabs';

const props = defineProps({ config: { type: Object, required: true } });
const previous = props.config.old || {};
const preferences = ref(props.config.preferences);
const reportType = ref(previous.reportType || 'projectReport');
const outputType = ref(String(previous.PDFtypeSelect ?? preferences.value.pdfTypeSelect));
const language = ref(previous.languageSelect || preferences.value.language);
const samplesPerPage = ref(Number(previous.noSamples ?? preferences.value.noSamples));
const highlight = ref(String(previous.showViolation ?? preferences.value.showViolation));
const template = ref(previous.templateSelect ?? props.config.preferredTemplate ?? '');
const selectedSamples = ref((Array.isArray(previous.exportSample) ? previous.exportSample : []).map(Number));
const allAssayIds = props.config.assays.map((assay) => Number(assay.id));
const selectedAssays = ref((props.config.canEditParameters && Array.isArray(previous.assay_ids) ? previous.assay_ids : allAssayIds).map(Number));
const restoredOrder = Array.isArray(previous.assay_order) ? previous.assay_order.map(Number) : props.config.assayOrder;
const order = ref([...new Set([...restoredOrder, ...allAssayIds])].filter((id) => allAssayIds.includes(id)));
const orderedAssays = computed(() => order.value.map((id) => props.config.assays.find((assay) => Number(assay.id) === id)));
const needsBulk = computed(() => outputType.value === '1' && samplesPerPage.value > 1 ? 1 : 0);
const visibleTemplates = computed(() => props.config.templates.filter((item) => item.language === language.value
    && [needsBulk.value, 2].includes(item.acceptsBulk)));
const selectedTemplate = computed(() => visibleTemplates.value.find((item) => item.folder === template.value));
const compatibleTemplates = computed(() => visibleTemplates.value.filter((item) => item.supported && !item.missingAssays.length));
const hasSingleSampleTemplate = computed(() => props.config.templates.some((item) => item.language === language.value && item.supported && item.acceptsBulk === 0));
const settingsChanged = computed(() => outputType.value !== String(preferences.value.pdfTypeSelect)
    || language.value !== preferences.value.language
    || highlight.value !== String(preferences.value.showViolation)
    || (outputType.value === '1' && samplesPerPage.value !== Number(preferences.value.noSamples)));
const refreshingPreferences = ref(false);
const preferencesError = ref('');
const submitErrors = ref([]);
const errorPanel = ref(null);
const submitting = ref(false);
const submitType = ref('create');
const draggingAssay = ref(null);
function restoreSubmissionState() {
    submitting.value = false;
}
onMounted(() => window.addEventListener('pageshow', restoreSubmissionState));
onBeforeUnmount(() => window.removeEventListener('pageshow', restoreSubmissionState));
const chosenSamples = computed(() => reportType.value === 'projectSample'
    ? props.config.samples.filter((sample) => selectedSamples.value.includes(Number(sample.id)))
    : props.config.samples);
const reportableAssays = computed(() => props.config.assays.filter((assay) => !assay.hidden && selectedAssays.value.includes(Number(assay.id))));
const requiredFooterScopes = computed(() => {
    const scopes = chosenSamples.value.map((sample) => {
        const assays = reportableAssays.value.filter((assay) => sample.assayIds.includes(Number(assay.id)));
        return assays.length ? Number(assays.some((assay) => assay.accredited)) : null;
    }).filter((scope) => scope !== null);
    return outputType.value === '1' && scopes.length ? [Math.max(...scopes)] : [...new Set(scopes)];
});
const setupIssues = computed(() => {
    const issues = [];
    if (!props.config.project.reference) issues.push('De projectreferentie ontbreekt. Controleer MESA_PROJECT_REFERENCE en de monsters van dit project.');
    if (!selectedTemplate.value) {
        issues.push(visibleTemplates.value.length
            ? 'Selecteer een beschikbaar rapportsjabloon. Controleer de analyse-associaties bij de rapportstijl.'
            : 'Geen rapportstijl past bij deze taal en dit aantal monsters per pagina.');
    } else {
        if (!selectedTemplate.value.supported) issues.push('De PDF-uitvoer van dit sjabloon is nog niet geport.');
        if (selectedTemplate.value.missingAssays.length) issues.push('Dit sjabloon is niet gekoppeld aan alle analyses van het project.');
        const missingScopes = requiredFooterScopes.value.filter((scope) => !selectedTemplate.value.footerScopes.includes(scope));
        if (missingScopes.length) issues.push(`Goedgekeurde voettekst ontbreekt voor ${selectedTemplate.value.folder}, ${language.value}, RvA-scope ${missingScopes.join(' / ')}.`);
    }
    if (!chosenSamples.value.length) issues.push('Selecteer minimaal een monster.');
    if (chosenSamples.value.length && !requiredFooterScopes.value.length) issues.push('De selectie bevat geen rapporteerbare parameters.');
    if (Number(props.config.project.auth_status) === 1 && !props.config.signatureAvailable) {
        issues.push('De Nederlandse handtekening van de autorisator ontbreekt. Voeg deze toe via de accountinstellingen of importeer de bestaande handtekening.');
    }
    return issues;
});

watch(visibleTemplates, () => {
    if (!compatibleTemplates.value.some((item) => item.folder === template.value)) {
        template.value = compatibleTemplates.value[0]?.folder || '';
    }
}, { immediate: true });

watch(outputType, (value) => {
    if (value === '0') reportType.value = 'projectReport';
});

function move(index, direction) {
    const target = index + direction;
    if (target < 0 || target >= order.value.length) return;
    [order.value[index], order.value[target]] = [order.value[target], order.value[index]];
}

function dropAssay(targetId) {
    const index = order.value.indexOf(draggingAssay.value);
    const target = order.value.indexOf(targetId);
    if (index >= 0 && target >= 0) {
        const [id] = order.value.splice(index, 1);
        order.value.splice(target, 0, id);
    }
    draggingAssay.value = null;
}

function applyPreferences() {
    outputType.value = String(preferences.value.pdfTypeSelect);
    language.value = preferences.value.language;
    samplesPerPage.value = Number(preferences.value.noSamples);
    highlight.value = String(preferences.value.showViolation);
}

async function reloadPreferences() {
    refreshingPreferences.value = true;
    preferencesError.value = '';
    try {
        const response = await fetch(props.config.preferencesUrl, { headers: { Accept: 'application/json' } });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || 'Klantvoorkeuren konden niet worden geladen.');
        if (payload.data.warning) throw new Error(payload.data.warning);
        preferences.value = payload.data;
        applyPreferences();
    } catch (error) {
        preferencesError.value = error.message;
    } finally {
        refreshingPreferences.value = false;
    }
}

async function submit(event) {
    event.preventDefault();
    if (submitting.value) return;
    submitErrors.value = [...setupIssues.value];
    if (submitErrors.value.length) {
        await nextTick();
        errorPanel.value?.focus();
        return;
    }
    const form = event.currentTarget;
    submitType.value = event.submitter?.value || 'create';
    submitting.value = submitType.value === 'create';
    form.target = submitting.value ? '_self' : '_blank';
    await nextTick();
    form.submit();
}
</script>

<template>
    <form method="POST" :action="config.action" class="report-generation-grid" @submit="submit">
        <input type="hidden" name="_token" :value="config.csrf">
        <input type="hidden" name="reportExportType" :value="submitType">
        <input v-for="id in order" :key="`order-${id}`" type="hidden" name="assay_order[]" :value="id">
        <input v-for="id in selectedAssays" :key="`assay-${id}`" type="hidden" name="assay_ids[]" :value="id">

        <div class="report-generation-column" data-report-column="project">
            <section class="sample-panel" aria-labelledby="export-project">
                <h2 id="export-project">Project uitvoeren</h2>
                <div class="sample-panel-body">
                    <dl class="report-summary">
                        <div><dt>Project</dt><dd><a class="text-link" :href="config.projectUrl">{{ config.project.project_name }}</a></dd></div>
                        <div><dt>Klant</dt><dd><a v-if="config.clientUrl" class="text-link" :href="config.clientUrl">{{ config.clientName }}</a><span v-else>{{ config.clientName }}</span></dd></div>
                    </dl>
                    <section class="mt-3 border-t border-line pt-3" aria-labelledby="report-preferences">
                        <h3 id="report-preferences" class="font-semibold">Portal Rapportage instellingen klant</h3>
                        <p v-if="preferences.portalProjectId" class="mt-2 text-muted">Portalorder {{ preferences.portalProjectId }}</p>
                        <p v-if="preferencesError || preferences.warning" class="report-feedback mt-2" role="alert">{{ preferencesError || preferences.warning }}</p>
                        <p v-else-if="preferences.foundSettings" class="report-feedback mt-2" role="status">{{ settingsChanged ? 'Klantvoorkeuren ingeladen, maar aangepast door gebruiker!' : 'Klantvoorkeuren succesvol geladen en toegepast.' }}</p>
                        <p v-else class="report-feedback mt-2" role="status">Geen klantvoorkeuren voor deze order gevonden. De standaardinstellingen worden gebruikt.</p>
                        <dl class="report-summary mt-3">
                            <div><dt>Type rapport</dt><dd>{{ Number(preferences.pdfTypeSelect) === 1 ? 'Verzamel rapport' : 'Individuele rapporten' }}</dd></div>
                            <div><dt>Taal</dt><dd>{{ preferences.language === 'en' ? 'Engels' : 'Nederlands' }}</dd></div>
                            <div><dt>Overschrijdingen markeren</dt><dd>{{ Number(preferences.showViolation) === 1 ? 'Ja' : 'Nee' }}</dd></div>
                        </dl>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <Button type="button" severity="secondary" label="Opnieuw laden en toepassen" :loading="refreshingPreferences" :disabled="submitting" @click="reloadPreferences" />
                            <a v-if="config.portalPreferencesUrl" class="text-link" :href="config.portalPreferencesUrl" target="_blank" rel="noopener noreferrer">Klantvoorkeuren in client-portal</a>
                        </div>
                        <p v-if="Number(config.project.special_type) === 2" class="report-feedback mt-3">Dit is een RODAC rapport, controleer handmatig rapport instellingen.</p>
                    </section>
                </div>
            </section>

            <section class="sample-panel" aria-labelledby="report-history">
                <h2 id="report-history">Eerdere rapportages van dit project</h2>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead><tr><th>Transactie</th><th>Datum</th><th>Type</th><th>Versie</th><th>Inhoud</th></tr></thead>
                        <tbody>
                            <tr v-for="entry in config.history" :key="entry.id">
                                <td>{{ entry.transaction ?? '-' }}</td><td>{{ entry.date }}</td><td>{{ entry.type }}</td><td>{{ entry.revision }}</td>
                                <td><a v-for="report in entry.content" :key="report.id" class="text-link block" :href="report.url" target="_blank" rel="noopener">{{ report.reference }}</a></td>
                            </tr>
                            <tr v-if="!config.history.length"><td colspan="5" class="empty-state">Geen rapportages</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="sample-panel" aria-labelledby="report-settings">
                <h2 id="report-settings">Uitvoer instellingen</h2>
                <div class="sample-panel-body grid gap-3">
                    <div class="field"><label for="report-output-type">PDF-uitvoer</label><select id="report-output-type" v-model="outputType" name="PDFtypeSelect"><option value="1">Verzamel rapport</option><option value="0">Individuele rapporten</option></select></div>
                    <div v-show="outputType === '1'" class="field"><label for="report-type">Rapportbereik</label><select id="report-type" v-model="reportType" name="reportType"><option value="projectReport">Volledig project</option><option value="projectSample">Geselecteerde monsters</option></select></div>
                    <div v-show="outputType === '1'" class="field"><label for="report-samples-per-page">Monsters per pagina</label><select id="report-samples-per-page" v-model="samplesPerPage" name="noSamples"><option v-for="count in 5" :key="count" :value="count">{{ count }}</option></select></div>
                    <div v-if="reportType === 'projectSample'" class="field">
                        <label for="report-sample-selection">Monsters selecteren</label>
                        <select id="report-sample-selection" v-model="selectedSamples" name="exportSample[]" multiple size="6">
                            <option v-for="sample in config.samples" :key="sample.id" :value="Number(sample.id)">MAZ-L{{ sample.barcode }} — {{ sample.client_description || '-' }}</option>
                        </select>
                    </div>
                    <p v-if="Number(config.project.auth_status) !== 1" class="report-feedback">Dit project is niet geautoriseerd. Het rapport krijgt het watermerk NIET GEAUTHORISEERD.</p>
                </div>
            </section>
        </div>

        <div class="report-generation-column" data-report-column="parameters">
            <section class="sample-panel" aria-labelledby="report-parameters">
                <h2 id="report-parameters">Parameters op rapport</h2>
                <Tabs value="order">
                    <TabPanels>
                        <TabPanel value="parameters">
                            <p v-if="!config.canEditParameters" class="mb-3 text-muted">Voor het aanpassen van de selectie is aanvullende toestemming nodig.</p>
                            <div class="grid gap-3">
                                <label v-for="assay in config.assays" :key="assay.id" class="flex items-start gap-2">
                                    <input v-model="selectedAssays" type="checkbox" :value="Number(assay.id)" :disabled="!config.canEditParameters">
                                    <span>{{ assay.name }} <small v-if="assay.hidden" class="text-muted">(niet rapporteerbaar)</small></span>
                                </label>
                            </div>
                        </TabPanel>
                        <TabPanel value="order">
                            <ol class="report-parameter-order">
                                <li v-for="(assay, index) in orderedAssays" :key="assay.id" :draggable="!submitting" @dragstart="draggingAssay = Number(assay.id)" @dragend="draggingAssay = null" @dragover.prevent @drop.prevent="dropAssay(Number(assay.id))">
                                    <GripVertical :size="14" class="shrink-0 text-muted" aria-hidden="true" />
                                    <span class="min-w-0 grow">{{ assay.name }}</span>
                                    <div class="flex shrink-0">
                                        <Button type="button" severity="secondary" variant="text" class="result-table-action" :disabled="index === 0 || submitting" :aria-label="`${assay.name} omhoog`" @click="move(index, -1)"><ArrowUp :size="14" /></Button>
                                        <Button type="button" severity="secondary" variant="text" class="result-table-action" :disabled="index === order.length - 1 || submitting" :aria-label="`${assay.name} omlaag`" @click="move(index, 1)"><ArrowDown :size="14" /></Button>
                                    </div>
                                </li>
                            </ol>
                        </TabPanel>
                    </TabPanels>
                    <TabList><Tab value="parameters">Parameters</Tab><Tab value="order">Volgorde</Tab></TabList>
                </Tabs>
            </section>
            <section class="sample-panel" aria-labelledby="report-sample-notes">
                <h2 id="report-sample-notes">Monster notities</h2>
                <div class="sample-panel-body grid gap-3">
                    <p v-for="sample in config.samples" :key="sample.id"><strong>{{ sample.barcode }}</strong><br><span class="whitespace-pre-line">{{ sample.sample_note || 'Geen notities' }}</span></p>
                    <p v-if="!config.samples.length" class="text-muted">Geen monsters gevonden.</p>
                </div>
            </section>
        </div>

        <div class="report-generation-column" data-report-column="execute">
            <section class="sample-panel" aria-labelledby="report-other-settings">
                <h2 id="report-other-settings">Overige instellingen</h2>
                <div class="sample-panel-body grid gap-3">
                    <div class="field"><label for="report-highlight">Overschreden waardes in rood?</label><select id="report-highlight" v-model="highlight" name="showViolation"><option value="1">Ja</option><option value="0">Nee</option></select></div>
                    <div class="field"><label for="report-language">Taal</label><select id="report-language" v-model="language" name="languageSelect"><option value="nl">Nederlands</option><option value="en">Engels</option></select></div>
                    <fieldset class="grid min-w-0 gap-3">
                        <legend class="mb-2 font-semibold">Rapport stijl</legend>
                        <div v-for="item in visibleTemplates" :key="item.id" class="min-w-0">
                            <label class="flex items-start gap-2">
                                <input v-model="template" type="radio" name="templateSelect" :value="item.folder" :disabled="!item.supported || item.missingAssays.length > 0">
                                <span>{{ item.name }}</span>
                            </label>
                            <p v-if="!item.supported" class="mt-1 text-muted">PDF-uitvoer nog niet geport.</p>
                            <details v-if="item.missingAssays.length" class="mt-2 text-muted">
                                <summary class="cursor-pointer">{{ item.missingAssays.length }} ontbrekende analyse-associaties</summary>
                                <ul class="mt-2 list-inside list-disc"><li v-for="assay in item.missingAssays" :key="assay.id">{{ assay.name }} ({{ assay.id }})</li></ul>
                            </details>
                            <a v-if="item.missingAssays.length && item.associationsUrl" class="text-link mt-2 block" :href="item.associationsUrl">Associaties voor {{ item.name }} beheren</a>
                            <p v-if="item.supported && !item.footerScopes.length" class="mt-2 text-muted">Nog geen goedgekeurde voetteksten voor dit sjabloon geconfigureerd.</p>
                        </div>
                        <p v-if="!visibleTemplates.length" role="status">Geen rapportstijl voor deze taal en dit aantal monsters per pagina.</p>
                        <div v-if="needsBulk === 1 && hasSingleSampleTemplate && !compatibleTemplates.length" class="report-feedback">
                            <p>maz2020 bevat 1 monster per pagina. Uw gekozen aantal wordt niet stilzwijgend aangepast.</p>
                            <Button type="button" severity="secondary" class="mt-2" label="Gebruik 1 monster per pagina" @click="samplesPerPage = 1" />
                        </div>
                        <a v-if="config.templatesUrl" class="text-link" :href="config.templatesUrl">Rapportsjablonen beheren</a>
                    </fieldset>
                </div>
            </section>

            <section class="sample-panel" aria-labelledby="report-execute">
                <h2 id="report-execute">Uitvoeren</h2>
                <div class="sample-panel-body grid gap-3">
                    <p v-if="config.clientNotes" class="report-feedback whitespace-pre-line">{{ config.clientNotes }}</p>
                    <div v-if="setupIssues.length" class="report-feedback">
                        <strong>Controleer voor het uitvoeren</strong>
                        <ul class="mt-2 list-inside list-disc"><li v-for="issue in setupIssues" :key="issue">{{ issue }}</li></ul>
                        <a v-if="config.footersUrl" class="text-link mt-2 block" :href="config.footersUrl">Rapportvoetteksten beheren</a>
                    </div>
                    <div v-if="submitErrors.length" ref="errorPanel" class="report-feedback" role="alert" tabindex="-1">
                        <strong>Rapport kan niet worden gemaakt</strong>
                        <ul class="mt-2 list-inside list-disc"><li v-for="error in submitErrors" :key="error">{{ error }}</li></ul>
                    </div>
                    <Button type="submit" value="create" label="Rapport maken" :loading="submitting" :disabled="submitting || refreshingPreferences" />
                    <Button type="submit" value="preview" label="Voorbeeld bekijken" severity="secondary" :disabled="submitting || refreshingPreferences" />
                </div>
            </section>
        </div>
    </form>
</template>
