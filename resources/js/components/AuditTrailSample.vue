<script setup>
import { computed, ref } from 'vue';
import { Building2, CalendarClock, ClipboardList, FlaskConical, FolderOpen, History, ListChecks, ShieldCheck, Sigma, Microscope } from '@lucide/vue';
import Message from 'openvue/message';
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

const props = defineProps({ sample: { type: Object, required: true }, links: { type: Object, required: true } });
const tab = ref('general');
const analysis = computed(() => props.sample.analysis);
const unavailable = computed(() => props.sample.assurance.reason === 'not_inoculated'
    ? 'Dit monster is nog niet ingezet; enkel de algemene gegevens en revisies zijn beschikbaar.'
    : 'Voor de inzetdatum van dit monster bestaat geen borgingsformulier; enkel de algemene gegevens en revisies zijn beschikbaar.');

const clientRows = computed(() => [
    { label: 'Opdrachtgever', value: props.sample.client?.name },
    { label: 'Contactpersoon', value: props.sample.client?.contact },
    { label: 'Adresgegevens', value: props.sample.client?.street },
    { label: 'Plaats', value: props.sample.client?.place },
]);
const projectRows = computed(() => {
    const project = props.sample.project;

    return [
        { label: 'Onderdeel van projectnummer', value: project?.reference || `#${project?.id}` },
        { label: 'Klant-referentie', value: project?.name },
        { label: 'Aantal monsters in project', value: project?.number_of_samples },
        { label: 'Aantal revisies van project', value: project?.revision },
        { label: 'Datum laatste versie geautoriseerd', value: project?.authorized_on ?? 'Nog niet geauthoriseerd geweest' },
        { label: 'Geautoriseerd door', value: project?.authorized_by ?? 'Nog niet geauthoriseerd geweest' },
        { label: 'Laatste analyse certificaat gegenereerd op', value: project?.reported_on ?? 'Nog niet gerapporteerd geweest' },
        { label: 'Laatste analyse certificaat gegenereerd door', value: project?.reported_by ?? 'Nog niet gerapporteerd geweest' },
        { label: 'Project opmerkingen', value: project?.notes ?? [] },
    ];
});
const sampleRows = computed(() => {
    const general = props.sample.general;

    return [
        { label: 'Datum bemonstering', value: general.date_sampling },
        { label: 'Datum en tijd ontvangst lab', value: [general.date_receive, general.time_receive].filter(Boolean).join(' ') },
        { label: 'Datum en tijd aanvang analyses', value: general.inoculated ?? 'Nog niet ingezet' },
        { label: 'Monster genomen door', value: general.sampling_by },
        { label: 'Monster methode', value: general.sampling_method },
        { label: 'Monster omschrijving', value: general.description },
        { label: 'Monster details', value: general.details },
        { label: 'Monster notities', value: general.notes },
        { label: 'Opgeslagen in bak in vriezer', value: general.stored_in },
        { label: 'Afgewogen en verdund op afweegstation', value: general.diluted_at },
        { label: 'THT-code (indien van toepassing)', value: general.tht_code },
    ];
});

const dateColumns = [
    { field: 'volg', header: 'Volgnr.' }, { field: 'name', header: 'Analyse' },
    { field: 'date_in', header: 'Datum in broedstoof' }, { field: 'time_in', header: 'Tijd in broedstoof' },
    { field: 'date_out', header: 'Datum uit broedstoof' }, { field: 'time_out', header: 'Tijd uit broedstoof' },
    { field: 'date_read', header: 'Datum aflezen' }, { field: 'time_read', header: 'Tijd aflezen' },
    { field: 'read_by', header: 'Afgelezen door' },
];
const assayColumns = [
    { field: 'volg', header: 'Volgnr.' }, { field: 'name', header: 'Analyse' }, { field: 'accredited', header: 'Q' },
    { field: 'technique', header: 'Techniek' }, { field: 'internal_reference', header: 'Intern ref. nummer' },
    { field: 'conformity', header: 'Conformiteit' }, { field: 'reference_method', header: 'Referentie methode' },
    { field: 'assay_id', header: 'Analyse ID' }, { field: 'revision', header: 'Revisie' },
];
const accumulationColumns = [{ field: 'media', header: 'Media' }, { field: 'tht', header: 'THT-datum', type: 'flag' }];
const mediaColumns = [
    { field: 'volg', header: 'Volgnr.' }, { field: 'name', header: 'Media' }, { field: 'tht', header: 'THT-datum', type: 'flag' },
    { field: 'supplements', each: 'name', header: 'Supplement' }, { field: 'supplements', each: 'tht', flag: true, header: 'THT-datum supplement' },
];
const materialColumns = [
    { field: 'volg', header: 'Volgnr.' }, { field: 'name', header: 'Materiaal' },
    { field: 'value', header: 'Controle', type: 'material', unit: 'unit' },
];
const confirmationColumns = [{ field: 'volg', header: 'Volgnr.' }, { field: 'name', header: 'Analyse' }, { field: 'performed_label', header: 'Bevestiging uitgevoerd' }];
const confirmationMediaColumns = [
    { field: 'volg', header: 'Volgnr.' }, { field: 'df', header: 'Verd.' }, { field: 'rep', header: 'Duplo' }, { field: 'name', header: 'Naam' },
    { field: 'tht', header: 'THT', type: 'flag' }, { field: 'set_up_on', header: 'Ingezet' }, { field: 'set_up_by', header: 'Door' },
    { field: 'read_on', header: 'Afgelezen' }, { field: 'read_by', header: 'Door' },
];
const noteColumns = [{ field: 'volg', header: 'Volgnr.' }, { field: 'note', header: 'Notities' }];
const dilutionHeaders = Array.from({ length: 11 }, (_, index) => (index === 0 ? '0' : `-${index}`));
const resultColumns = [
    { field: 'volg', header: 'Volgnr.' },
    ...dilutionHeaders.map((header, index) => ({ field: `count_${index}`, header })),
    { field: 'end_result', header: 'Eindresultaat' }, { field: 'ct_value', header: 'Ct-waarde' },
];
const confirmationResultColumns = [
    { field: 'volg', header: 'Volgnr.' }, { field: 'df', header: 'Verd.' }, { field: 'rep', header: 'Duplo' }, { field: 'name', header: 'Bevestigingstest' },
    ...[1, 2, 3, 4, 5].map((number) => ({ field: `kve${number}`, header: `KVE ${number}` })),
    { field: 'positive_control', header: 'Positieve controle' }, { field: 'negative_control', header: 'Negatieve controle' }, { field: 'blank_control', header: 'Blanco' },
];

const confirmationRows = computed(() => (analysis.value?.confirmations ?? []).map((row) => ({ ...row, performed_label: row.performed ? 'Ja' : 'Nee' })));
const resultRows = computed(() => (analysis.value?.results ?? []).map((row) => ({
    volg: row.volg, end_result: row.end_result, ct_value: row.ct_value,
    ...Object.fromEntries(row.counts.map((count, index) => [`count_${index}`, count])),
})));
const confirmationResultRows = computed(() => (analysis.value?.confirmation_results ?? []).map((row) => ({
    ...row, ...Object.fromEntries(row.kve.map((value, index) => [`kve${index + 1}`, value])),
})));
const assuranceFormUrl = computed(() => auditUrl(props.links.assuranceForm, '__DATE__', props.sample.assurance.form?.form_date));
</script>

<template>
    <Tabs v-model:value="tab" class="panel-tabs wrap-tabs audit-tabs">
        <TabList>
            <Tab value="general"><ClipboardList :size="14" />Algemeen</Tab>
            <Tab value="analysis"><Microscope :size="14" />Analyses</Tab>
            <Tab value="assurance" :disabled="!analysis"><ShieldCheck :size="14" />Borging</Tab>
            <Tab value="confirmations" :disabled="!analysis"><ListChecks :size="14" />Bevestigingen</Tab>
            <Tab value="results" :disabled="!analysis"><Sigma :size="14" />Resultaten</Tab>
            <Tab value="revisions"><History :size="14" />Revisies</Tab>
        </TabList>
        <TabPanels>
            <TabPanel value="general">
                <div class="audit-stack">
                    <h2 class="audit-print-heading">Monster {{ sample.barcode }}: algemeen</h2>
                    <AuditSection title="Opdrachtgever" :icon="Building2">
                        <AuditFacts :rows="clientRows">
                            <template #value="{ row }">
                                <a v-if="row.label === 'Opdrachtgever' && row.value && links.client && sample.client" class="text-link" :href="auditUrl(links.client, '__CLIENT__', sample.client.id)">{{ row.value }}</a>
                                <template v-else>{{ row.value || '-' }}</template>
                            </template>
                        </AuditFacts>
                    </AuditSection>
                    <AuditSection v-if="sample.project" title="Projectgegevens" :icon="FolderOpen">
                        <AuditFacts :rows="projectRows">
                            <template #value="{ row }">
                                <template v-if="row.label === 'Project opmerkingen'">
                                    <p v-for="note in row.value" :key="note.label" class="audit-note-line"><strong>{{ note.label }}:</strong> {{ note.text }}</p>
                                    <template v-if="!row.value.length">-</template>
                                </template>
                                <a v-else-if="row.label === 'Onderdeel van projectnummer' && links.project" class="text-link" :href="auditUrl(links.project, '__PROJECT__', sample.project.id)">{{ row.value }}</a>
                                <template v-else>{{ row.value ?? '-' }}</template>
                            </template>
                        </AuditFacts>
                    </AuditSection>
                    <AuditSection title="Algemene monstergegevens" :icon="FlaskConical">
                        <AuditFacts :rows="sampleRows">
                            <template #value="{ row }">
                                <template v-if="row.label === 'Monster notities'">
                                    <p v-for="(note, index) in row.value" :key="index" class="audit-note-line"><strong>{{ note.author }}</strong> <small>{{ note.created_at }}</small><br>{{ note.text }}</p>
                                    <template v-if="!row.value.length">-</template>
                                </template>
                                <template v-else>{{ row.value || '-' }}</template>
                            </template>
                        </AuditFacts>
                    </AuditSection>
                </div>
            </TabPanel>
            <TabPanel value="analysis">
                <div class="audit-stack">
                    <h2 class="audit-print-heading">Monster {{ sample.barcode }}: analyses</h2>
                    <Message v-if="!analysis" severity="info" :closable="false">{{ unavailable }}</Message>
                    <template v-else>
                        <AuditSection title="Algemene analysegegevens" :icon="ClipboardList">
                            <template #tools>
                                <Tag :severity="sample.assurance.form.is_complete ? 'success' : 'warn'">{{ sample.assurance.form.is_complete ? 'Borgingsformulier compleet' : 'Borgingsformulier incompleet' }}</Tag>
                                <a v-if="links.assuranceForm" class="text-link" :href="assuranceFormUrl">Borgingsformulier {{ sample.assurance.form.date }}</a>
                            </template>
                            <AuditFacts :rows="[{ label: 'Monster ingezet door', value: analysis.set_up_by }, { label: 'Platen gegoten door', value: analysis.poured_by }]" />
                        </AuditSection>
                        <AuditSection title="Datums voor onderzochte parameters van dit monster" :icon="CalendarClock"><AuditTable :rows="analysis.dates" :columns="dateColumns" /></AuditSection>
                        <AuditSection title="Onderzochte parameters van dit monster" :icon="Microscope"><AuditTable :rows="analysis.assays" :columns="assayColumns" /></AuditSection>
                    </template>
                </div>
            </TabPanel>
            <TabPanel value="assurance">
                <div v-if="analysis" class="audit-stack">
                    <h2 class="audit-print-heading">Monster {{ sample.barcode }}: borging</h2>
                    <AuditSection title="Ophoping- en verdunningsvloeistoffen" :icon="ShieldCheck"><AuditTable :rows="analysis.accumulation" :columns="accumulationColumns" /></AuditSection>
                    <AuditSection title="Benodigde media voor analyses" :icon="ShieldCheck"><AuditTable :rows="analysis.media" :columns="mediaColumns" /></AuditSection>
                    <AuditSection title="Benodigd materiaal voor deze analyses" :icon="ShieldCheck"><AuditTable :rows="analysis.material" :columns="materialColumns" /></AuditSection>
                </div>
            </TabPanel>
            <TabPanel value="confirmations">
                <div v-if="analysis" class="audit-stack">
                    <h2 class="audit-print-heading">Monster {{ sample.barcode }}: bevestigingen</h2>
                    <AuditSection title="Uitgevoerde bevestigingen voor deze analyses" :icon="ListChecks"><AuditTable :rows="confirmationRows" :columns="confirmationColumns" /></AuditSection>
                    <AuditSection title="Benodigde media voor deze bevestigingen" :icon="ListChecks"><AuditTable :rows="analysis.confirmation_media" :columns="confirmationMediaColumns" /></AuditSection>
                    <AuditSection title="Eventueel gemaakte opmerkingen bij bevestigingen" :icon="ListChecks"><AuditTable :rows="analysis.confirmation_notes" :columns="noteColumns" /></AuditSection>
                </div>
            </TabPanel>
            <TabPanel value="results">
                <div v-if="analysis" class="audit-stack">
                    <h2 class="audit-print-heading">Monster {{ sample.barcode }}: resultaten</h2>
                    <AuditSection title="Resultaten van uitgevoerde telling per analyse inclusief eindresultaat" :icon="Sigma"><AuditTable :rows="resultRows" :columns="resultColumns" /></AuditSection>
                    <AuditSection title="Resultaten uitgevoerde bevestigingstesten per analyse" :icon="Sigma"><AuditTable :rows="confirmationResultRows" :columns="confirmationResultColumns" /></AuditSection>
                </div>
            </TabPanel>
            <TabPanel value="revisions">
                <div class="audit-stack">
                    <h2 class="audit-print-heading">Monster {{ sample.barcode }}: revisies</h2>
                    <template v-if="analysis">
                        <AuditSection v-for="assay in analysis.assay_revisions" :key="assay.volg" :title="`Analyse revisies: ${assay.volg}. ${assay.name}`" :icon="History"><AuditRevisions :revisions="assay.revisions" /></AuditSection>
                    </template>
                    <AuditSection title="Project revisies" :icon="History"><AuditRevisions :revisions="sample.revisions.project" /></AuditSection>
                    <AuditSection title="Monster revisies" :icon="History"><AuditRevisions :revisions="sample.revisions.sample" /></AuditSection>
                    <AuditSection v-if="analysis" title="Borgingsformulier revisies" :icon="History"><AuditRevisions :revisions="sample.revisions.assurance_form" /></AuditSection>
                </div>
            </TabPanel>
        </TabPanels>
    </Tabs>
</template>
