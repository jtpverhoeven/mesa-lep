<script setup>
import { nextTick, onMounted, ref } from 'vue';
import { Barcode, Building2, Download, Euro, FlaskConical, LoaderCircle, Network, Search, Trash2, X } from '@lucide/vue';
import Column from 'openvue/column';
import ColumnGroup from 'openvue/columngroup';
import ConfirmDialog from 'openvue/confirmdialog';
import DataTable from 'openvue/datatable';
import DatePicker from 'openvue/datepicker';
import Row from 'openvue/row';
import Select from 'openvue/select';
import { useConfirm } from 'openvue/useconfirm';
import ResearchProfileClientPicker from './ResearchProfileClientPicker.vue';
import { billingStatuses, useBillingStore } from '../stores/billingStore';

const props = defineProps({
    endpoints: { type: Object, required: true },
    csrf: { type: String, required: true },
});
const store = useBillingStore();
const confirm = useConfirm();
const barcode = ref('');
const scanInput = ref(null);

store.configure(props.endpoints, props.csrf);

async function focusScan() {
    await nextTick();
    scanInput.value?.focus();
    scanInput.value?.select();
}

function ask(message, accept) {
    confirm.require({
        header: 'Bevestigen', message, acceptLabel: 'Ja', rejectLabel: 'Nee', rejectProps: { severity: 'secondary' }, accept,
    });
}

async function scan() {
    const scanned = barcode.value;
    const status = await store.scan(scanned);
    await focusScan();
    if (status === 'client_mismatch' && scanned) {
        ask('De gescande barcode hoort bij een andere klant. Wilt u de huidige selectie wissen en deze barcode als nieuwe selectie starten?', () => store.startNewSelection(scanned));
    }
}

async function reset() {
    barcode.value = '';
    store.reset();
    await focusScan();
}

function toggleBilled(row) {
    ask(row.billed ? 'Terug instellen op "Niet gefactureerd"?' : 'Instellen als gefactureerd?', () => store.setBilled(row, !row.billed));
}

function download() {
    if (store.exportUrl) window.location.href = store.exportUrl;
    else store.notice = 'Geen projecten geselecteerd';
}

onMounted(() => {
    store.load();
    focusScan();
});
</script>

<template>
    <div class="billing-page" :aria-busy="store.loading">
        <p v-if="store.notice" class="notice error" role="alert">{{ store.notice }}</p>
        <div class="billing-grid">
            <div class="billing-side">
                <section class="sample-panel">
                    <h2><Building2 :size="16" aria-hidden="true" />Selecteren op klant en periode</h2>
                    <form class="sample-panel-body billing-scan" @submit.prevent="store.searchRange()">
                        <ResearchProfileClientPicker :endpoint="endpoints.clients" :model-value="store.client" @update:model-value="store.changeClient" />
                        <div class="field">
                            <label for="billing-date-from">Inzetdatum van</label>
                            <DatePicker v-model="store.dateFrom" input-id="billing-date-from" date-format="dd-mm-yy" placeholder="Van" append-to="self" fluid />
                        </div>
                        <div class="field">
                            <label for="billing-date-to">Inzetdatum t/m</label>
                            <DatePicker v-model="store.dateTo" input-id="billing-date-to" date-format="dd-mm-yy" placeholder="Tot" append-to="self" fluid />
                        </div>
                        <div class="field">
                            <label id="billing-range-status-label">Facturatiestatus</label>
                            <Select v-model="store.rangeStatus" :options="billingStatuses" option-label="label" option-value="value" aria-labelledby="billing-range-status-label" append-to="self" fluid />
                        </div>
                        <div class="billing-scan-actions">
                            <button class="button primary" type="submit" :disabled="store.loading"><Search :size="15" aria-hidden="true" />Zoeken</button>
                        </div>
                    </form>
                </section>

                <section class="sample-panel">
                    <h2><Search :size="16" aria-hidden="true" />Scannen voor facturatie</h2>
                    <form class="sample-panel-body billing-scan" @submit.prevent="scan">
                        <label class="sr-only" for="billing-scan">Project referentie</label>
                        <div class="project-search-control">
                            <Barcode :size="16" aria-hidden="true" />
                            <input id="billing-scan" ref="scanInput" v-model="barcode" autocomplete="off" placeholder="Scan of typ een project referentie" @focus="$event.target.select()" @click="$event.target.select()">
                        </div>
                        <Select v-model="store.scanStatus" :options="billingStatuses" option-label="label" option-value="value" aria-label="Facturatiestatus scannen" append-to="self" fluid />
                        <div class="billing-scan-actions">
                            <button class="button primary" type="button" @click="reset"><Trash2 :size="15" aria-hidden="true" />Opnieuw beginnen</button>
                            <button class="button primary" type="submit" :disabled="store.loading"><Search :size="15" aria-hidden="true" />Zoeken</button>
                        </div>
                    </form>
                </section>
            </div>

            <section class="sample-panel">
                <h2>
                    <Euro :size="16" aria-hidden="true" />Project gegevens voor facturatie
                    <LoaderCircle v-if="store.loading" class="spin" :size="15" aria-hidden="true" />
                </h2>
                <div class="sample-panel-body billing-sections">
                    <h3><Building2 :size="15" aria-hidden="true" />Klant</h3>
                    <div class="billing-client">
                        <span>{{ store.overview.client?.name }}</span>
                        <span v-if="store.overview.client">Debiteur nummer: {{ store.overview.client.debit_number }}</span>
                        <span v-if="store.overview.client">Klant categorie: {{ store.overview.client.categories.join(',') }}</span>
                    </div>

                    <h3><Network :size="15" aria-hidden="true" />Ingescande projecten</h3>
                    <div class="table-scroll">
                        <DataTable :value="store.overview.project_rows" data-key="id" size="small" table-class="billing-table">
                            <Column header="Facturatie">
                                <template #body="{ data }">
                                    <button class="project-status billing-status-toggle" :class="data.billed ? 'success' : 'warning'" type="button" :data-billed="data.billed ? 1 : 0" @click="toggleBilled(data)">{{ data.billed ? 'Gefactureerd' : 'Nog te factureren' }}</button>
                                </template>
                            </Column>
                            <Column header="Status">
                                <template #body="{ data }"><span class="project-status" :class="data.status_tone">{{ data.status }}</span></template>
                            </Column>
                            <Column header="Referentie MAZ">
                                <template #body="{ data }"><a :href="data.url" target="_blank">MAZ-L {{ data.reference }}</a></template>
                            </Column>
                            <Column field="revision" header="Revisie" />
                            <Column field="project_name" header="Referentie klant" />
                            <Column header="Aantal monsters">
                                <template #body="{ data }">
                                    <span class="billing-sample-count">
                                        {{ data.samples_count }}
                                        <span v-if="data.automatic_rodac > 0" class="project-status warning" title="Een of meer normale monsters zijn automatisch als RODAC herkend.">RODAC</span>
                                        <span v-if="data.has_rodac" class="project-status warning" title="Dit project bevat expliciete RODAC-monsters.">RODAC</span>
                                        <span v-if="data.has_legionella" class="project-status info" title="Dit project bevat Legionella-monsters.">Legionella</span>
                                    </span>
                                </template>
                            </Column>
                            <Column field="sampling_date" header="Bemonsterdatum" />
                            <Column field="received_date" header="Ontvangstdatum" />
                            <Column header="Inzetdatum">
                                <template #body="{ data }"><span class="billing-multiline">{{ data.inoculation_date }}</span></template>
                            </Column>
                            <Column header="Verwijder" class="billing-number">
                                <template #body="{ data }">
                                    <button class="icon-button danger" type="button" :title="`MAZ-L ${data.reference} verwijderen`" :aria-label="`MAZ-L ${data.reference} verwijderen`" :disabled="store.loading" @click="store.drop(data.id)"><X :size="15" /></button>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                    <div class="billing-actions">
                        <button class="button primary" type="button" @click="store.markBilled()">Instelling als gefactureerd</button>
                        <button class="button primary" type="button" @click="download"><Download :size="15" aria-hidden="true" />Download uitvoer</button>
                    </div>

                    <h3><FlaskConical :size="15" aria-hidden="true" />Monsters &amp; toeslagen</h3>
                    <div v-if="store.overview.sample_types.length" class="table-scroll">
                        <DataTable :value="store.overview.sample_types" data-key="type" size="small" table-class="billing-table">
                            <ColumnGroup type="header">
                                <Row>
                                    <Column :colspan="5" />
                                    <Column header="Toeslagen" :colspan="2" class="billing-center" />
                                </Row>
                                <Row>
                                    <Column header="Type monster" />
                                    <Column header="Totaal aantal" class="billing-number" />
                                    <Column header="Projecten" class="billing-number" />
                                    <Column header="Mengmonsters" class="billing-number" />
                                    <Column header="Ontvangst data" class="billing-number" />
                                    <Column header="waarvan met 50%" class="billing-number" />
                                    <Column header="waarvan met 100%" class="billing-number" />
                                </Row>
                            </ColumnGroup>
                            <Column field="header" />
                            <Column field="count" class="billing-number" />
                            <Column field="project_count" class="billing-number" />
                            <Column field="mengmonsters" class="billing-number" />
                            <Column field="receipt_date_count" class="billing-number" />
                            <Column class="billing-number">
                                <template #body="{ data }"><span :class="{ 'billing-surcharge': data.count50 > 0 }">{{ data.count50 }}</span></template>
                            </Column>
                            <Column class="billing-number">
                                <template #body="{ data }"><span :class="{ 'billing-surcharge': data.count100 > 0 }">{{ data.count100 }}</span></template>
                            </Column>
                        </DataTable>
                    </div>

                    <h3><Search :size="15" aria-hidden="true" />Analyses</h3>
                    <p v-if="store.projects.length && !store.overview.articles.length" class="notice billing-empty" role="status"><strong>Geen resultaten</strong> Geen resultaten gevonden voor dit project</p>
                    <div v-else-if="store.overview.articles.length" class="table-scroll">
                        <DataTable :value="store.overview.articles" data-key="assay_base" size="small" table-class="billing-table">
                            <Column field="name" header="Analyses" />
                            <Column field="article_code" header="Artikel code" />
                            <Column field="total" header="Aantal" class="billing-number" />
                            <Column header="Aantal met bevestiging" class="billing-number">
                                <template #body="{ data }">{{ data.conf_requested_count }}<template v-if="data.positives !== null"> - Waarvan positief:{{ data.positives }}</template></template>
                            </Column>
                        </DataTable>
                    </div>
                </div>
            </section>
        </div>
        <ConfirmDialog :draggable="false" />
    </div>
</template>
