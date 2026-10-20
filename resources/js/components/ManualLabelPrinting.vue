<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';
import { Barcode, CalendarDays, ChevronDown, ChevronUp, FlaskConical, LoaderCircle, Printer, Settings2, Tag } from '@lucide/vue';
import Button from 'openvue/button';
import Checkbox from 'openvue/checkbox';
import DatePicker from 'openvue/datepicker';
import InputNumber from 'openvue/inputnumber';
import InputText from 'openvue/inputtext';
import Select from 'openvue/select';
import AppConfirmDialog from './AppConfirmDialog.vue';

const props = defineProps({ options: { type: Object, required: true }, printUrl: { type: String, required: true }, csrf: { type: String, required: true } });
const barcodeInput = ref(null);
const rangeStartInput = ref(null);
const rangeStopInput = ref(null);
const legionellaStartInput = ref(null);
const advanced = ref(false);
const running = ref(false);
const activeMode = ref('scan');
const confirmationVisible = ref(false);
const pending = ref(null);
const errors = ref({});
const requestError = ref('');
const status = ref('');
const busy = computed(() => running.value || confirmationVisible.value);
const errorMessages = computed(() => Object.values(errors.value).flat());
const printerOptions = computed(() => [{ id: 0, name: 'Standaard printer' }, ...props.options.printers]);
const labelOptions = computed(() => [{ id: 'default', name: 'Standaard label' }, ...props.options.labels]);
const assayOptions = computed(() => [{ id: 0, name: 'Alles' }, ...props.options.assayTypes]);
const legionellaMatrices = ['A', 'B', 'C'];
const legionella = reactive({ leg_from: null, leg_to: null, leg_matrix: 'A', leg_printer: props.options.printers[0]?.id ?? null });
const initialDate = () => {
    const [year, month, day] = props.options.today.split('-').map(Number);
    return new Date(year, month - 1, day);
};
const form = reactive({
    barcode: '', range_start: initialDate(), range_stop: initialDate(), range_type_select: 'i',
    bar_range_start: '', bar_range_stop: '', print_normal_samples: true, print_rodac_samples: false,
    date_offset: initialDate(), profile_ids: [], duplicate_label: false,
    also_print: false, also_print_amount: 1,
    also_print_selection: props.options.labels.find(label => label.id === props.options.extraLabel)?.id ?? props.options.labels[0]?.id ?? null,
    print_sample: false, print_sample_amount: 1, printer: 0,
    assay_type_select: 0, sample_label_select: 'default', assay_label_select: 'default',
});
const invalidDateEntries = reactive({ range_start: false, range_stop: false, date_offset: false });

watch(() => form.duplicate_label, (duplicate) => { if (duplicate) form.profile_ids = []; });

function trackDateInput(field, event) {
    const parts = /^(\d{1,2})-(\d{1,2})-(\d{4})$/.exec(event.target.value.trim());
    const date = form[field];
    invalidDateEntries[field] = !parts || !(date instanceof Date)
        || Number(parts[1]) !== date.getDate() || Number(parts[2]) !== date.getMonth() + 1 || Number(parts[3]) !== date.getFullYear();
    if (!invalidDateEntries[field]) delete errors.value[field];
}

function acceptDate(field) {
    invalidDateEntries[field] = false;
    delete errors.value[field];
}

function dateString(date) {
    if (!(date instanceof Date) || Number.isNaN(date.getTime())) return '';
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function focusInput(input) {
    const control = input?.$el?.querySelector('input') ?? input?.$el;
    control?.focus();
    control?.select();
}

async function restoreFocus() {
    await nextTick();
    focusInput(activeMode.value === 'legionella' ? legionellaStartInput.value : activeMode.value === 'sample_range' ? rangeStartInput.value : barcodeInput.value);
}

async function request(payload) {
    running.value = true;
    errors.value = {};
    requestError.value = '';
    status.value = '';

    try {
        const response = await fetch(props.printUrl, {
            method: 'POST', credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': props.csrf },
            body: JSON.stringify(payload),
        });
        if (response.redirected || [401, 419].includes(response.status)) {
            requestError.value = 'Je sessie is verlopen. Vernieuw deze pagina.';
            return;
        }
        if (response.status === 422) {
            errors.value = (await response.json()).errors ?? {};
            return;
        }
        if (response.status === 409) {
            const result = await response.json();
            if (result.confirmation_required) {
                pending.value = result;
                confirmationVisible.value = true;
            } else {
                requestError.value = result.message;
            }
            return;
        }
        if (!response.ok) {
            requestError.value = response.status === 403 ? 'Je hebt geen toegang tot scan-to-print.' : 'De printopdracht is niet bevestigd. Controleer de wachtrij voordat je opnieuw print.';
            return;
        }
        const result = await response.json();
        status.value = result.message;
        confirmationVisible.value = false;
        pending.value = null;
        if (activeMode.value === 'scan') form.barcode = '';
    } catch {
        requestError.value = 'De verbinding is verbroken. Controleer de wachtrij voordat je opnieuw print.';
    } finally {
        running.value = false;
        if (!confirmationVisible.value) await restoreFocus();
    }
}

async function print(mode) {
    if (busy.value || (form.duplicate_label && mode !== 'scan')) return;
    const dates = mode === 'date_range' ? ['date_offset', 'range_start', 'range_stop'] : ['date_offset'];
    const invalid = dates.filter(field => invalidDateEntries[field]);
    if (invalid.length) {
        errors.value = Object.fromEntries(invalid.map(field => [field, ['Voer een geldige datum in (dd-mm-jjjj).']]));
        return;
    }
    activeMode.value = mode;
    await request({ ...form, mode, barcode: form.barcode.trim(), bar_range_start: form.bar_range_start.trim(), bar_range_stop: form.bar_range_stop.trim(),
        print_sample: form.print_sample && !form.duplicate_label,
        range_start: dateString(form.range_start), range_stop: dateString(form.range_stop), date_offset: dateString(form.date_offset),
    });
}

async function confirmPrint() {
    if (!pending.value || running.value) return;
    await request({ confirmation_token: pending.value.confirmation_token });
}

async function printLegionella() {
    if (busy.value) return;
    activeMode.value = 'legionella';
    await request({ mode: 'legionella', ...legionella });
}

function closeConfirmation(visible) {
    if (running.value) return;
    confirmationVisible.value = visible;
    if (!visible) {
        pending.value = null;
        restoreFocus();
    }
}

onMounted(() => focusInput(barcodeInput.value));
</script>

<template>
    <div class="manual-printing min-w-0">        
        <div v-if="!options.enabled" class="notice" role="status">Printen is uitgeschakeld.</div>
        <div v-if="requestError || errorMessages.length" class="notice error" role="alert">
            <strong>{{ requestError || 'Controleer de invoer.' }}</strong>
            <ul v-if="errorMessages.length"><li v-for="message in errorMessages" :key="message">{{ message }}</li></ul>
        </div>
        <div v-if="status" class="notice success" role="status">{{ status }}</div>

        <div class="grid min-w-0 grid-cols-1 items-start gap-6 md:grid-cols-2 xl:grid-cols-4">
            <div class="sample-create-column">
                <form class="sample-panel" @submit.prevent="print('scan')">
                    <h2 id="print-scan-title"><Barcode :size="16" aria-hidden="true" />Scan barcode</h2>
                    <fieldset class="sample-panel-body min-w-0" aria-labelledby="print-scan-title" :disabled="busy">
                        <div class="field">
                            <label for="print-barcode" class="sr-only">Barcode</label>
                            <InputText id="print-barcode" ref="barcodeInput" v-model="form.barcode" placeholder="Barcode" maxlength="64" autocomplete="off" :disabled="busy" :invalid="Boolean(errors.barcode)" fluid />
                        </div>
                        <div class="mt-3 flex justify-end">
                            <Button type="submit" :disabled="busy || !form.barcode.trim()">
                                <LoaderCircle v-if="running && activeMode === 'scan'" class="spin" :size="15" aria-hidden="true" /><Printer v-else :size="15" aria-hidden="true" />
                                {{ running && activeMode === 'scan' ? 'Verwerken' : 'Printen' }}
                            </Button>
                        </div>
                    </fieldset>
                </form>

                <form class="sample-panel" @submit.prevent="print('date_range')">
                    <h2 id="print-date-range-title"><CalendarDays :size="16" aria-hidden="true" />Print datum bereik</h2>
                    <fieldset class="sample-panel-body min-w-0" aria-labelledby="print-date-range-title" :disabled="busy">
                        <div class="grid gap-3">
                            <div class="field"><label id="print-date-type-label">Datumtype</label><Select v-model="form.range_type_select" :options="options.dateTypes" option-label="name" option-value="id" aria-labelledby="print-date-type-label" :disabled="busy" append-to="self" fluid /></div>
                            <div class="field"><label for="print-date-start">Van</label><DatePicker v-model="form.range_start" input-id="print-date-start" date-format="dd-mm-yy" :disabled="busy" :invalid="Boolean(errors.range_start) || invalidDateEntries.range_start" append-to="self" fluid @input="trackDateInput('range_start', $event)" @date-select="acceptDate('range_start')" /></div>
                            <div class="field"><label for="print-date-stop">Tot en met</label><DatePicker v-model="form.range_stop" input-id="print-date-stop" date-format="dd-mm-yy" :disabled="busy" :invalid="Boolean(errors.range_stop) || invalidDateEntries.range_stop" append-to="self" fluid @input="trackDateInput('range_stop', $event)" @date-select="acceptDate('range_stop')" /></div>
                        </div>
                        <div class="mt-3 flex justify-end"><Button type="submit" :disabled="busy || form.duplicate_label">
                            <LoaderCircle v-if="running && activeMode === 'date_range'" class="spin" :size="15" aria-hidden="true" /><Printer v-else :size="15" aria-hidden="true" />
                            {{ running && activeMode === 'date_range' ? 'Verwerken' : 'Printen' }}
                        </Button></div>
                    </fieldset>
                </form>

                <form class="sample-panel" @submit.prevent="print('sample_range')">
                    <h2 id="print-sample-range-title"><FlaskConical :size="16" aria-hidden="true" />Print monster bereik</h2>
                    <fieldset class="sample-panel-body min-w-0" aria-labelledby="print-sample-range-title" :disabled="busy">
                        <div class="grid gap-3">
                            <div class="field"><label for="print-range-start">Van</label><InputText id="print-range-start" ref="rangeStartInput" v-model="form.bar_range_start" maxlength="64" autocomplete="off" :disabled="busy" :invalid="Boolean(errors.bar_range_start)" fluid @keydown.enter.prevent="focusInput(rangeStopInput)" /></div>
                            <div class="field"><label for="print-range-stop">Tot en met</label><InputText id="print-range-stop" ref="rangeStopInput" v-model="form.bar_range_stop" maxlength="64" autocomplete="off" :disabled="busy" :invalid="Boolean(errors.bar_range_stop)" fluid /></div>
                            <label class="print-check"><Checkbox v-model="form.print_normal_samples" input-id="print-normal" binary :disabled="busy" /><span>Normale analyse &amp; legionella monsters</span></label>
                            <label class="print-check"><Checkbox v-model="form.print_rodac_samples" input-id="print-rodac" binary :disabled="busy" /><span>RODAC monsters</span></label>
                        </div>
                        <div class="mt-3 flex justify-end"><Button type="submit" :disabled="busy || form.duplicate_label || !form.bar_range_start.trim() || !form.bar_range_stop.trim()">
                            <LoaderCircle v-if="running && activeMode === 'sample_range'" class="spin" :size="15" aria-hidden="true" /><Printer v-else :size="15" aria-hidden="true" />
                            {{ running && activeMode === 'sample_range' ? 'Verwerken' : 'Printen' }}
                        </Button></div>
                    </fieldset>
                </form>

                <section class="sample-panel">
                    <h2 id="print-date-title"><CalendarDays :size="16" aria-hidden="true" />Datum instellen</h2>
                    <fieldset class="sample-panel-body min-w-0" aria-labelledby="print-date-title" :disabled="busy">
                        <div class="field"><label for="print-inoculation-date">Print stickers voor inzetdag</label><DatePicker v-model="form.date_offset" input-id="print-inoculation-date" date-format="dd-mm-yy" :disabled="busy" :invalid="Boolean(errors.date_offset) || invalidDateEntries.date_offset" append-to="self" fluid @input="trackDateInput('date_offset', $event)" @date-select="acceptDate('date_offset')" /></div>
                    </fieldset>
                </section>
            </div>

            <div class="sample-create-column">
                <section class="sample-panel">
                    <h2 id="print-options-title"><Settings2 :size="16" aria-hidden="true" />Print opties</h2>
                    <fieldset class="sample-panel-body min-w-0" aria-labelledby="print-options-title" :disabled="busy">
                        <div class="grid gap-5">
                            <div class="grid max-h-72 gap-3 overflow-y-auto pr-1">
                                <label v-for="profile in options.profiles" :key="profile.id" class="print-check"><Checkbox v-model="form.profile_ids" :input-id="`print-profile-${profile.id}`" :value="profile.id" :disabled="busy || form.duplicate_label" /><span>{{ profile.name }}</span></label>
                                <p v-if="!options.profiles.length" class="muted">Geen printprofielen beschikbaar.</p>
                            </div>
                            <label class="print-check"><Checkbox v-model="form.duplicate_label" input-id="print-duplicate" binary :disabled="busy" /><span>Dupliceer label</span></label>
                            <div class="print-inline-option flex flex-wrap items-center gap-x-1 gap-y-2">
                                <label class="print-check"><Checkbox v-model="form.also_print" input-id="print-extra" binary :disabled="busy" /><span>Print ook</span></label>
                                <label for="print-extra-copies" class="sr-only">Aantal extra labels</label>
                                <InputNumber v-model="form.also_print_amount" class="print-copy-count" input-id="print-extra-copies" :min="1" :max="9999" :use-grouping="false" :disabled="busy || !form.also_print" />
                                <span class="shrink-0">keer label</span>
                                <label id="print-extra-design-label" class="sr-only">Extra label</label>
                                <Select v-model="form.also_print_selection" class="print-extra-design" :options="options.labels" option-label="name" option-value="id" aria-labelledby="print-extra-design-label" :disabled="busy || !form.also_print" append-to="self" filter />
                            </div>
                            <div class="print-inline-option flex flex-wrap items-center gap-x-1 gap-y-2">
                                <label class="print-check"><Checkbox v-model="form.print_sample" input-id="print-sample" binary aria-label="Monster labels" :disabled="busy || form.duplicate_label" /><span>Print</span></label>
                                <label for="print-sample-copies" class="sr-only">Aantal monsterlabels</label>
                                <InputNumber v-model="form.print_sample_amount" class="print-copy-count" input-id="print-sample-copies" :min="1" :max="9999" :use-grouping="false" :disabled="busy || !form.print_sample || form.duplicate_label" />
                                <span>monster labels</span>
                            </div>
                            <Button type="button" severity="secondary" :disabled="busy" :aria-expanded="advanced" aria-controls="print-advanced" @click="advanced = !advanced">
                                <ChevronUp v-if="advanced" :size="15" aria-hidden="true" /><ChevronDown v-else :size="15" aria-hidden="true" />Geavanceerde opties
                            </Button>
                        </div>
                    </fieldset>
                </section>
                <section v-show="advanced" id="print-advanced" class="sample-panel">
                    <h2 id="print-advanced-title"><Tag :size="16" aria-hidden="true" />Geavanceerde opties</h2>
                    <fieldset class="sample-panel-body min-w-0" aria-labelledby="print-advanced-title" :disabled="busy">
                        <div class="grid gap-4">
                            <div class="field"><label id="print-assay-type-label">Beperk tot analysetype</label><Select v-model="form.assay_type_select" :options="assayOptions" option-label="name" option-value="id" aria-labelledby="print-assay-type-label" :disabled="busy" append-to="self" fluid /></div>
                            <div class="field"><label id="print-sample-design-label">Monsterlabel</label><Select v-model="form.sample_label_select" :options="labelOptions" option-label="name" option-value="id" aria-labelledby="print-sample-design-label" :disabled="busy" append-to="self" filter fluid /></div>
                            <div class="field"><label id="print-assay-design-label">Analyselabel</label><Select v-model="form.assay_label_select" :options="labelOptions" option-label="name" option-value="id" aria-labelledby="print-assay-design-label" :disabled="busy" append-to="self" filter fluid /></div>
                        </div>
                    </fieldset>
                </section>
            </div>

            <section class="sample-panel">
                <h2 id="print-printer-title"><Printer :size="16" aria-hidden="true" />Print opties</h2>
                <fieldset class="sample-panel-body min-w-0" aria-labelledby="print-printer-title" :disabled="busy">
                    <div class="field"><label id="print-printer-label">Print naar</label><Select v-model="form.printer" :options="printerOptions" option-label="name" option-value="id" aria-labelledby="print-printer-label" :disabled="busy" append-to="self" filter fluid /></div>
                </fieldset>
            </section>

            <form class="sample-panel" @submit.prevent="printLegionella">
                <h2 id="print-legionella-title"><Printer :size="16" aria-hidden="true" />Legionella stickers</h2>
                <fieldset class="sample-panel-body min-w-0" aria-labelledby="print-legionella-title" :disabled="busy">
                    <div class="grid gap-3">
                        <div class="field"><label id="print-legionella-matrix-label">Matrix</label><Select v-model="legionella.leg_matrix" :options="legionellaMatrices" aria-labelledby="print-legionella-matrix-label" :disabled="busy" :invalid="Boolean(errors.leg_matrix)" append-to="self" fluid /></div>
                        <div class="field"><label for="print-legionella-start">Van</label><InputNumber ref="legionellaStartInput" v-model="legionella.leg_from" input-id="print-legionella-start" :min="0" :use-grouping="false" :disabled="busy" :invalid="Boolean(errors.leg_from)" fluid /></div>
                        <div class="field"><label for="print-legionella-stop">Tot en met</label><InputNumber v-model="legionella.leg_to" input-id="print-legionella-stop" :min="0" :use-grouping="false" :disabled="busy" :invalid="Boolean(errors.leg_to)" fluid /></div>
                        <div class="field"><label id="print-legionella-printer-label">Print naar</label><Select v-model="legionella.leg_printer" :options="options.printers" option-label="name" option-value="id" aria-labelledby="print-legionella-printer-label" :disabled="busy" :invalid="Boolean(errors.leg_printer)" append-to="self" filter fluid /></div>
                    </div>
                    <div class="mt-3 flex justify-end"><Button type="submit" :disabled="busy || legionella.leg_from === null || legionella.leg_to === null || !legionella.leg_printer">
                        <LoaderCircle v-if="running && activeMode === 'legionella'" class="spin" :size="15" aria-hidden="true" /><Printer v-else :size="15" aria-hidden="true" />
                        {{ running && activeMode === 'legionella' ? 'Verwerken' : 'Printen' }}
                    </Button></div>
                </fieldset>
            </form>
        </div>

        <AppConfirmDialog :visible="confirmationVisible" title="Grote printopdracht" :message="pending?.message ?? ''" confirm-label="Printen" :loading="running" :error="requestError || errorMessages.join(' ')" @update:visible="closeConfirmation" @confirm="confirmPrint">
            <template #confirm-icon><Printer :size="15" aria-hidden="true" /></template>
        </AppConfirmDialog>
    </div>
</template>

<style scoped>
.print-check { display:flex; align-items:flex-start; gap:9px; cursor:pointer; }
.print-check span { min-width:0; overflow-wrap:anywhere; line-height:20px; }
.print-inline-option .print-check { align-items:center; }
.print-copy-count { flex:0 0 48px; width:48px; min-width:0; }
.print-copy-count :deep(input) { width:100%; min-width:0; padding:6px; }
.print-extra-design { flex:1 1 70px; min-width:60px; }
.print-extra-design :deep([data-pc-section='label']) { min-width:0; padding:6px; }
</style>