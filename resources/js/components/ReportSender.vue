<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Download, Ellipsis, FileArchive, FileText, Mail, Network, X } from '@lucide/vue';
import Tab from 'openvue/tab';
import TabList from 'openvue/tablist';
import TabPanel from 'openvue/tabpanel';
import TabPanels from 'openvue/tabpanels';
import Tabs from 'openvue/tabs';
import { getEcho } from '../echo.js';
import { postJson } from '../senderApi';
import { useKeyringStore } from '../stores/keyringStore';
import KeyringNotice from './KeyringNotice.vue';
import SenderAddressing from './SenderAddressing.vue';
import SenderFilesTable from './SenderFilesTable.vue';
import SenderMessageBlock from './SenderMessageBlock.vue';
import SenderSubmit from './SenderSubmit.vue';

const props = defineProps({ config: { type: Object, required: true } });
const keyringStore = useKeyringStore();
const projectId = Number(props.config.project.id);
const exportLocked = computed(() => keyringStore.isLockedByOther('export', projectId));
const groups = props.config.contactGroups ?? [];
const files = props.config.files ?? { attachments: [], orphaned: [] };

const addressing = reactive({
    receivers: groups.filter((group) => group.checked).map((group) => group.id),
    extra: '',
    cc: '',
    bcc: props.config.bcc ?? '',
});
const messages = reactive({
    message: props.config.templates.reports.template ?? '',
    messageAtt: props.config.templates.report_with_files.template ?? '',
    messageFiles: props.config.templates.for_files.template ?? '',
});
const activeTab = ref('addressing');
const viewerReportId = ref(props.config.reports[0]?.id ?? null);
const viewerReport = computed(() => props.config.reports.find((report) => report.id === viewerReportId.value) ?? null);
const exportAll = ref('all');
const selectedReports = ref([]);
const attachmentHashes = ref([]);
const orphanHashes = ref([]);
const mixed = ref(false);
const mixedError = ref('');
const returnToAuthorized = ref(true);
let mixedRequest = 0;

const hasAttachments = computed(() => attachmentHashes.value.length > 0);
const showNormal = computed(() => !hasAttachments.value || mixed.value);
const showAttachments = computed(() => hasAttachments.value);
const showOrphans = computed(() => orphanHashes.value.length > 0);
const reportSelectionVisible = computed(() => exportAll.value === 'selected' && props.config.reports.length > 1);
const selectionError = computed(() => (exportAll.value === 'selected' && !selectedReports.value.length ? 'Maak een selectie van de rapporten die u wilt verzenden.' : ''));
const payload = computed(() => ({
    transaction: props.config.transaction,
    receivers: addressing.receivers,
    extraReceiver: addressing.extra,
    cc: addressing.cc,
    bcc: addressing.bcc,
    message: showNormal.value ? messages.message : '',
    messageAtt: showAttachments.value ? messages.messageAtt : '',
    messageFiles: showOrphans.value ? messages.messageFiles : '',
    exportAll: exportAll.value === 'all',
    selectedReports: exportAll.value === 'selected' ? selectedReports.value : [],
    includeSampleFiles: attachmentHashes.value,
    includeOrphanedSampleFiles: orphanHashes.value,
}));

watch(attachmentHashes, async (selected) => {
    const request = ++mixedRequest;
    mixedError.value = '';
    if (!selected.length) {
        mixed.value = false;
        return;
    }
    try {
        const result = await postJson(props.config.urls.mixed, { selected_files: selected });
        if (request === mixedRequest) mixed.value = result === true;
    } catch {
        if (request === mixedRequest) mixedError.value = 'Kon niet controleren welke rapporten bijlagen krijgen.';
    }
});

onMounted(() => {
    keyringStore.configure(props.config.keyrings, getEcho);
    void keyringStore.acquire('export', projectId, props.config.editability?.keyring ?? null);
});
onBeforeUnmount(() => {
    void keyringStore.release('export', projectId);
});
</script>

<template>
    <div class="status-notices"><KeyringNotice type="export" :id="projectId" /></div>

    <div v-if="!exportLocked" class="sender-layout">
        <section class="sample-panel sender-viewer" aria-labelledby="send-viewer">
            <h2 id="send-viewer"><FileText :size="14" aria-hidden="true" /> Gegenereerd rapport</h2>
            <div class="sender-viewer-toolbar">
                <select v-model="viewerReportId" class="form-control" aria-label="Rapport">
                    <option v-for="report in config.reports" :key="report.id" :value="report.id">{{ report.name }}</option>
                </select>
                <a v-if="viewerReport" class="button" :href="viewerReport.downloadUrl"><Download :size="14" aria-hidden="true" /> Download</a>
                <a v-if="config.zipUrl" class="button" :href="config.zipUrl"><FileArchive :size="14" aria-hidden="true" /> Alles downloaden</a>
            </div>
            <iframe v-if="viewerReport" :key="viewerReport.id" class="sender-viewer-frame" :src="viewerReport.viewUrl" :title="viewerReport.name"></iframe>
        </section>

        <div class="grid content-start gap-3">
            <template v-if="config.unauthorized">
                <section class="sample-panel" aria-labelledby="send-disabled">
                    <h2 id="send-disabled"><X :size="14" aria-hidden="true" /> Verzenden uitgeschakeld</h2>
                    <div class="sample-panel-body grid gap-3">
                        <p class="report-feedback danger" role="alert">
                            <strong>Verzenden is niet mogelijk</strong><br>
                            Dit rapport kan niet verzonden worden, het project was niet geautoriseerd op het moment dat dit rapport werd gemaakt.
                        </p>
                        <p class="text-muted">Oudere, geautoriseerde, versies van dit project kunnen eventueel wel worden verstuurd of gedownload. Indien oudere versies bestaan van dit project, worden deze hieronder weergegeven.</p>
                    </div>
                    <div class="table-scroll">
                        <table class="data-table">
                            <thead><tr><th>Transactie</th><th>Datum</th><th>Type</th><th>Versie</th><th>Inhoud</th><th><span class="sr-only">Verzenden</span></th></tr></thead>
                            <tbody>
                                <tr v-for="entry in config.versions" :key="entry.id">
                                    <td>{{ entry.transaction ?? '-' }}</td><td>{{ entry.date }}</td><td>{{ entry.type }}</td><td>{{ entry.revision }}</td>
                                    <td><a v-for="report in entry.content" :key="report.id" class="text-link block" :href="report.url" target="_blank" rel="noopener">{{ report.reference }}</a></td>
                                    <td><a v-if="entry.send_url" class="icon-button" :href="entry.send_url" title="Verzenden of downloaden" aria-label="Verzenden of downloaden"><Mail :size="16" /></a></td>
                                </tr>
                                <tr v-if="!config.versions.length"><td colspan="6" class="empty-state">Geen oudere versies</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>
                <section class="sample-panel">
                    <h2>Andere opties</h2>
                    <div class="sample-panel-body flex flex-wrap gap-3">
                        <a class="button primary" :href="config.urls.project">Open project</a>
                        <a class="button" :href="config.urls.completed">Overzicht afgeronde projecten</a>
                        <a class="button" :href="config.urls.authorized">Overzicht geautoriseerde projecten</a>
                    </div>
                </section>
            </template>

            <template v-else>
                <section class="sample-panel" aria-label="Email versturen">
                    <h2><Mail :size="14" aria-hidden="true" /> Email Rapportage naar klant</h2>
                    <dl class="report-summary sample-panel-body">
                        <div><dt>Project</dt><dd><a class="text-link" :href="config.urls.project">{{ config.project.reference }}</a></dd></div>
                        <div v-if="config.clientName"><dt>Klant</dt><dd>{{ config.clientName }}</dd></div>
                    </dl>
                    <Tabs v-model:value="activeTab" class="sender-tabs">
                        <TabPanels>
                            <TabPanel value="addressing">
                                <SenderAddressing v-model:receivers="addressing.receivers" v-model:extra="addressing.extra" v-model:cc="addressing.cc" v-model:bcc="addressing.bcc" :groups="groups" :error="config.contactGroupsError" />
                            </TabPanel>
                            <TabPanel value="message">
                                <div class="grid gap-4">
                                    <SenderMessageBlock v-show="showNormal" v-model="messages.message" caption="Email voor rapportages zonder bestanden" :selector="config.templates.reports" :default-language="config.defaultLanguage" :template-url="config.urls.template" @bcc="addressing.bcc = $event" />
                                    <SenderMessageBlock v-show="showAttachments" v-model="messages.messageAtt" caption="Email voor rapportages met bestanden." :selector="config.templates.report_with_files" :default-language="config.defaultLanguage" :template-url="config.urls.template" @bcc="addressing.bcc = $event" />
                                    <SenderMessageBlock v-show="showOrphans" v-model="messages.messageFiles" caption="Email voor losse bestanden." :selector="config.templates.for_files" :default-language="config.defaultLanguage" :template-url="config.urls.template" @bcc="addressing.bcc = $event" />
                                </div>
                            </TabPanel>
                            <TabPanel value="other">
                                <div class="grid gap-4">
                                    <!-- <div v-if="config.zipUrl" class="field">
                                        <label for="send-export-type">Welke rapporten?</label>
                                        <select id="send-export-type" v-model="exportAll" class="form-control">
                                            <option value="all">Alles in deze uitvoer</option>
                                            <option value="selected">Selectie</option>
                                        </select>
                                    </div>
                                    <fieldset v-if="reportSelectionVisible" class="grid gap-2">
                                        <legend class="font-semibold">Maak een selectie</legend>
                                        <label v-for="report in config.reports" :key="report.id" class="flex items-start gap-2">
                                            <input v-model="selectedReports" type="checkbox" :value="report.id"> <span class="wrap-anywhere">{{ report.name }}</span>
                                        </label>
                                    </fieldset> -->
                                    <div class="field">
                                        <label for="send-transaction">Transaction Id</label>
                                        <input id="send-transaction" class="form-control" type="text" :value="config.transaction" readonly>
                                    </div>
                                </div>
                            </TabPanel>
                        </TabPanels>
                        <TabList>
                            <Tab value="addressing"><Network :size="14" aria-hidden="true" />Adressering</Tab>
                            <Tab value="message"><FileText :size="14" aria-hidden="true" />Bericht</Tab>
                            <Tab value="other"><Ellipsis :size="14" aria-hidden="true" />Overig</Tab>
                        </TabList>
                    </Tabs>
                </section>

                <p v-if="!files.attachments.length && !files.orphaned.length" class="report-feedback" role="status">Geen zichtbare bestanden gevonden</p>

                <section v-if="files.attachments.length" class="sample-panel" aria-labelledby="send-attachments">
                    <h2 id="send-attachments"><span class="attention-pulse" aria-hidden="true"></span>Wilt u deze bijlagen van dit project ook verzenden?</h2>
                    <p v-if="mixedError" class="report-feedback danger m-3" role="alert">{{ mixedError }}</p>
                    <SenderFilesTable v-model="attachmentHashes" :files="files.attachments" :history-url="config.urls.history" label="Bijlagen bij dit rapport" />
                </section>

                <section v-if="files.orphaned.length" class="sample-panel" aria-labelledby="send-orphans">
                    <h2 id="send-orphans"><span class="attention-pulse" aria-hidden="true"></span>Deze bestanden ook versturen?</h2>
                    <div class="sample-panel-body">
                        <p class="text-muted">Deze bestanden zijn niet gekoppeld aan een specifiek rapport, maar zijn wel zichtbaar voor dit project. Indien u deze bestanden ook wilt verzenden, vink ze dan aan.</p>
                    </div>
                    <SenderFilesTable v-model="orphanHashes" :files="files.orphaned" :history-url="config.urls.history" label="Losse bestanden" />
                </section>

                <div class="flex flex-wrap items-center gap-3">
                    <SenderSubmit :url="config.urls.mail" label="Verzenden - Email &amp; portal" :payload="payload" :has-receivers="addressing.receivers.length > 0 || addressing.extra.trim() !== ''" :validation-error="selectionError" :redirect-url="returnToAuthorized ? config.urls.authorized : ''" />
                    <label class="flex items-center gap-2"><input v-model="returnToAuthorized" type="checkbox"> Terug naar geautoriseerd overzicht na verzenden</label>
                    <a class="button" :href="config.urls.reportForm">Terug naar rapport uitvoeren</a>
                </div>
            </template>
        </div>
    </div>
</template>
