<script setup>
import { computed, ref } from 'vue';
import { FileText, FlaskConical, FolderOpen, History, Paperclip, Send, StickyNote } from '@lucide/vue';
import Tab from 'openvue/tab';
import TabList from 'openvue/tablist';
import TabPanel from 'openvue/tabpanel';
import TabPanels from 'openvue/tabpanels';
import Tabs from 'openvue/tabs';
import Tag from 'openvue/tag';
import AuditFacts from './AuditFacts.vue';
import AuditRevisions from './AuditRevisions.vue';
import AuditSection from './AuditSection.vue';
import AuditTable from './AuditTable.vue';
import { auditUrl } from '../auditTrail.js';

const props = defineProps({ project: { type: Object, required: true }, links: { type: Object, required: true } });
const tab = ref('overview');
const overview = computed(() => props.project.overview);
const reference = computed(() => overview.value.reference || `#${props.project.id}`);

const overviewRows = computed(() => {
    const data = overview.value;

    return [
        { label: 'Projectnummer', value: reference.value },
        { label: 'Klant-referentie', value: data.name },
        { label: 'Opdrachtgever', value: data.client?.name },
        { label: 'Aangemaakt op', value: data.created_on },
        { label: 'Aangemaakt door', value: data.created_by },
        { label: 'Bron', value: data.sources },
        { label: 'Portal-bestelling', value: data.portal_id },
        { label: 'Status', value: data.status },
        { label: 'Revisie / afdrukversie', value: `${data.revision} / ${data.print_version}` },
        { label: 'Inzetdatum', value: data.inoculation_date },
        { label: 'Verwacht gereed', value: data.expected_date },
        { label: 'Gereed gekomen op', value: data.became_ready_on },
        { label: 'Geautoriseerd', value: data.authorized_on ? `${data.authorized_on} door ${data.authorized_by}` : 'Niet geautoriseerd' },
        { label: 'Rapport gegenereerd', value: data.reported_on ? `${data.reported_on} door ${data.reported_by}` : 'Nog niet gerapporteerd' },
        { label: 'Autorisatie geblokkeerd', value: data.blocked },
        { label: 'Gefactureerd', value: data.billed ? 'Ja' : 'Nee' },
        ...data.extra_fields.map((field) => ({ label: field.label, value: field.value })),
        ...data.fields.map((field) => ({ label: field.label, value: field.value })),
    ];
});

const sampleColumns = [
    { field: 'barcode', header: 'Barcode' }, { field: 'description', header: 'Omschrijving' }, { field: 'type', header: 'Type' },
    { field: 'source', header: 'Bron' }, { field: 'registered_on', header: 'Aangemeld' }, { field: 'registered_by', header: 'Door' },
    { field: 'inoculated_on', header: 'Ingezet' }, { field: 'analyses', header: 'Analyses' },
];
const reportNoteColumns = [{ field: 'label', header: 'Notitie bij' }, { field: 'text', header: 'Tekst' }, { field: 'updated_at', header: 'Laatst gewijzigd' }];
const internalNoteColumns = [
    { field: 'barcode', header: 'Monster' }, { field: 'author', header: 'Auteur' }, { field: 'created_at', header: 'Geplaatst' }, { field: 'text', header: 'Tekst' },
];
const reportColumns = [
    { field: 'transaction', header: 'Transactie' }, { field: 'date', header: 'Datum' }, { field: 'type', header: 'Type' }, { field: 'reference', header: 'Rapport' },
    { field: 'temporary_label', header: 'Voorlopig' }, { field: 'authorized_label', header: 'Geautoriseerd' }, { field: 'sample', header: 'Monster-ID' },
];
const fileColumns = [
    { field: 'barcode', header: 'Monster' }, { field: 'name', header: 'Bestand' }, { field: 'uploaded_at', header: 'Geüpload' },
    { field: 'visible_label', header: 'Zichtbaar voor klant' }, { field: 'delivery_label', header: 'Verzonden' },
];
const reportRows = computed(() => props.project.reports.map((report) => ({
    ...report, temporary_label: report.temporary ? 'Ja' : 'Nee', authorized_label: report.was_authorized ? 'Ja' : 'Nee',
})));
const fileRows = computed(() => props.project.files.map((file) => ({
    ...file,
    visible_label: file.visible_for_client ? 'Ja' : 'Nee',
    delivery_label: file.deliveries.length
        ? file.deliveries.map((delivery) => `${delivery.sent_on} door ${delivery.sent_by} (${delivery.method}${delivery.recipients ? `: ${delivery.recipients}` : ''})`).join('\n')
        : 'Nog niet verstuurd',
})));
</script>

<template>
    <Tabs v-model:value="tab" class="panel-tabs wrap-tabs audit-tabs">
        <TabList>
            <Tab value="overview"><FolderOpen :size="14" />Overzicht</Tab>
            <Tab value="samples"><FlaskConical :size="14" />Monsters</Tab>
            <Tab value="notes"><StickyNote :size="14" />Notities</Tab>
            <Tab value="reports"><FileText :size="14" />Rapporten &amp; verzending</Tab>
            <Tab value="timeline"><History :size="14" />Tijdlijn</Tab>
        </TabList>
        <TabPanels>
            <TabPanel value="overview">
                <div class="audit-stack">
                    <h2 class="audit-print-heading">Project {{ reference }}: overzicht</h2>
                    <AuditSection title="Projectgegevens" :icon="FolderOpen">
                        <AuditFacts :rows="overviewRows">
                            <template #value="{ row }">
                                <a v-if="row.label === 'Projectnummer' && links.project" class="text-link" :href="auditUrl(links.project, '__PROJECT__', project.id)">{{ row.value }}</a>
                                <a v-else-if="row.label === 'Opdrachtgever' && row.value && links.client" class="text-link" :href="auditUrl(links.client, '__CLIENT__', overview.client.id)">{{ row.value }}</a>
                                <span v-else-if="row.label === 'Bron'" class="flex flex-wrap gap-1">
                                    <Tag v-for="source in row.value" :key="source.source" severity="secondary">{{ source.source }} ({{ source.count }})</Tag>
                                    <template v-if="!row.value.length">-</template>
                                </span>
                                <span v-else-if="row.label === 'Status'" class="project-status" :class="overview.status_tone">{{ row.value }}</span>
                                <template v-else-if="row.label === 'Autorisatie geblokkeerd'">
                                    <template v-if="row.value"><Tag severity="warn">Geblokkeerd door {{ row.value.by }}</Tag> {{ row.value.message }}</template>
                                    <template v-else>Nee</template>
                                </template>
                                <template v-else>{{ row.value === null || row.value === undefined || row.value === '' ? '-' : row.value }}</template>
                            </template>
                        </AuditFacts>
                    </AuditSection>
                </div>
            </TabPanel>
            <TabPanel value="samples">
                <div class="audit-stack">
                    <h2 class="audit-print-heading">Project {{ reference }}: monsters</h2>
                    <AuditSection title="Monsters in dit project" :icon="FlaskConical"><AuditTable :rows="project.samples" :columns="sampleColumns" /></AuditSection>
                </div>
            </TabPanel>
            <TabPanel value="notes">
                <div class="audit-stack">
                    <h2 class="audit-print-heading">Project {{ reference }}: notities</h2>
                    <AuditSection title="Opmerkingen op het rapport" :icon="StickyNote"><AuditTable :rows="project.notes.report" :columns="reportNoteColumns" empty-text="Geen rapportopmerkingen." /></AuditSection>
                    <AuditSection title="Interne monsternotities" :icon="StickyNote"><AuditTable :rows="project.notes.internal" :columns="internalNoteColumns" empty-text="Geen interne notities." /></AuditSection>
                </div>
            </TabPanel>
            <TabPanel value="reports">
                <div class="audit-stack">
                    <h2 class="audit-print-heading">Project {{ reference }}: rapporten &amp; verzending</h2>
                    <AuditSection title="Gegenereerde rapporten" :icon="FileText"><AuditTable :rows="reportRows" :columns="reportColumns" empty-text="Er zijn nog geen rapporten gegenereerd." /></AuditSection>
                    <AuditSection title="Verstuurd naar de klant (e-mail via portal)" :icon="Send"><AuditRevisions :revisions="project.sendings" empty-text="Er is nog niets verstuurd." /></AuditSection>
                    <AuditSection title="Bestanden van monsters" :icon="Paperclip"><AuditTable :rows="fileRows" :columns="fileColumns" empty-text="Geen bestanden." /></AuditSection>
                </div>
            </TabPanel>
            <TabPanel value="timeline">
                <div class="audit-stack">
                    <h2 class="audit-print-heading">Project {{ reference }}: tijdlijn</h2>
                    <AuditSection title="Alle vastgelegde wijzigingen van project en monsters" :icon="History"><AuditRevisions :revisions="project.revisions" /></AuditSection>
                </div>
            </TabPanel>
        </TabPanels>
    </Tabs>
</template>
