<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { FlaskConical, LoaderCircle, Minus, Plus, Save } from '@lucide/vue';
import Column from 'openvue/column';
import DataTable from 'openvue/datatable';
import InputNumber from 'openvue/inputnumber';
import InputText from 'openvue/inputtext';
import Select from 'openvue/select';
import Textarea from 'openvue/textarea';

const props = defineProps({
    initial: { type: Object, required: true },
    options: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
    actionUrl: { type: String, required: true },
    testUrl: { type: String, required: true },
    cancelUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    updating: { type: Boolean, default: false },
});

const draft = reactive({ name: props.initial.name ?? '', profile: props.initial.profile ?? '' });
const testInput = reactive({ barcode: '', printer: null, dateOffset: 0 });
const submitting = ref(false);
const testing = ref(false);
const testErrors = ref({});
const requestError = ref('');
const result = ref(null);
const canSave = computed(() => draft.name.trim() && draft.name.length <= 64 && draft.profile.trim());
const canTest = computed(() => testInput.barcode.trim() && draft.profile.trim() && !testing.value && !submitting.value);
const printerSummary = computed(() => {
    const printers = new Map();
    for (const label of result.value?.labels ?? []) {
        const printer = printers.get(label.print_to) ?? { id: label.print_to, name: label.printer_name, copies: 0 };
        printer.copies += label.copies;
        printers.set(label.print_to, printer);
    }
    return [...printers.values()];
});

watch(() => [draft.profile, testInput.barcode, testInput.printer, testInput.dateOffset], () => {
    result.value = null;
    testErrors.value = {};
    requestError.value = '';
});

function error(field) {
    return testErrors.value[field]?.[0] ?? props.errors[field]?.[0] ?? '';
}

function submit(event) {
    if (!canSave.value || submitting.value || testing.value) {
        event.preventDefault();
        return;
    }
    submitting.value = true;
}

async function testProfile() {
    if (!canTest.value) return;
    testing.value = true;
    testErrors.value = {};
    requestError.value = '';
    result.value = null;

    try {
        const response = await fetch(props.testUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': props.csrf },
            body: JSON.stringify({ profile: draft.profile, barcode: testInput.barcode.trim(), printer_override: testInput.printer, date_offset: testInput.dateOffset ?? 0 }),
        });
        if (response.status === 422) {
            testErrors.value = (await response.json()).errors ?? {};
            return;
        }
        if (!response.ok) {
            requestError.value = ({ 401: 'Je sessie is verlopen. Meld je opnieuw aan.', 403: 'Je hebt geen toegang tot printprofielen.', 419: 'Je sessie is verlopen. Vernieuw deze pagina.', 429: 'Te veel tests gestart. Probeer het over een minuut opnieuw.' })[response.status] ?? 'De test is mislukt. Probeer het opnieuw.';
            return;
        }
        result.value = await response.json();
    } catch {
        requestError.value = 'De verbinding is verbroken. Probeer het opnieuw.';
    } finally {
        testing.value = false;
    }
}
</script>

<template>
    <div class="profile-editor grid min-w-0 grid-cols-1 gap-8 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
        <form class="min-w-0" method="POST" :action="actionUrl" @submit="submit">
            <input type="hidden" name="_token" :value="csrf">
            <input v-if="updating" type="hidden" name="_method" value="PUT">
            <fieldset class="form-section">
                <legend>Printprofiel</legend>
                <div class="field mb-5">
                    <label for="print-profile-name">Naam</label>
                    <InputText id="print-profile-name" v-model="draft.name" name="name" maxlength="64" required :invalid="Boolean(error('name'))" aria-describedby="print-profile-name-error" fluid />
                    <small v-if="error('name')" id="print-profile-name-error" class="error-text">{{ error('name') }}</small>
                </div>
                <div class="field">
                    <label for="print-profile-script">Printscript</label>
                    <Textarea id="print-profile-script" v-model="draft.profile" name="profile" class="script-editor" rows="20" wrap="off" spellcheck="false" autocomplete="off" autocapitalize="off" maxlength="65535" required :disabled="testing" :invalid="Boolean(error('profile'))" aria-describedby="print-profile-script-error" fluid />
                    <small v-if="error('profile')" id="print-profile-script-error" class="error-text" role="alert">{{ error('profile') }}</small>
                </div>
            </fieldset>
            <div class="form-actions flex-wrap">
                <button class="button primary" type="submit" :disabled="!canSave || submitting || testing">
                    <LoaderCircle v-if="submitting" :size="16" class="spin" aria-hidden="true" /><Save v-else :size="16" aria-hidden="true" />
                    {{ updating ? 'Wijzigingen opslaan' : 'Printprofiel aanmaken' }}
                </button>
                <a class="button" :href="cancelUrl">Annuleren</a>
            </div>
            <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2">
                <details v-for="reference in [{ key: 'labelDesigns', name: 'Labelontwerpen' }, { key: 'printers', name: 'Printers' }]" :key="reference.key" class="min-w-0 border-t border-[var(--line)] pt-3">
                    <summary class="cursor-pointer font-semibold">{{ reference.name }} <span class="muted">({{ options[reference.key].length }})</span></summary>
                    <div class="mt-3 max-h-64 overflow-auto">
                        <DataTable :value="options[reference.key]" table-class="data-table" :unstyled="true">
                            <Column field="id" header="ID" />
                            <Column field="name" header="Naam"><template #body="{ data }"><span class="whitespace-normal break-words">{{ data.name }}</span></template></Column>
                            <template #empty><span class="muted">Geen gegevens beschikbaar.</span></template>
                        </DataTable>
                    </div>
                </details>
            </div>
        </form>

        <section id="test" class="min-w-0 scroll-mt-6" aria-labelledby="print-profile-test-title">
            <form @submit.prevent="testProfile">
                <fieldset class="form-section">
                    <legend id="print-profile-test-title">Test / Dry-run</legend>
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div class="field sm:col-span-2">
                            <label for="print-profile-barcode">Barcode</label>
                            <InputText id="print-profile-barcode" v-model="testInput.barcode" maxlength="32" autocomplete="off" :disabled="testing" :invalid="Boolean(error('barcode'))" aria-describedby="print-profile-barcode-error" fluid />
                            <small v-if="error('barcode')" id="print-profile-barcode-error" class="error-text" role="alert">{{ error('barcode') }}</small>
                        </div>
                        <div class="field sm:col-span-2">
                            <label id="print-profile-printer-label">Printeroverride</label>
                            <Select v-model="testInput.printer" :options="options.printers" option-label="name" option-value="id" placeholder="Printers uit het script" show-clear filter aria-labelledby="print-profile-printer-label" :disabled="testing" :invalid="Boolean(error('printer_override'))" append-to="self" fluid />
                            <small v-if="error('printer_override')" class="error-text">{{ error('printer_override') }}</small>
                        </div>
                        <div class="field sm:col-span-2">
                            <label for="print-profile-date-offset">Datumverschuiving (dagen)</label>
                            <InputNumber v-model="testInput.dateOffset" input-id="print-profile-date-offset" :min="-365" :max="365" :use-grouping="false" :disabled="testing" :invalid="Boolean(error('date_offset'))" show-buttons :pt="{ incrementButton: { 'aria-label': 'Een dag later', title: 'Een dag later' }, decrementButton: { 'aria-label': 'Een dag eerder', title: 'Een dag eerder' } }" fluid>
                                <template #incrementbuttonicon><Plus :size="12" aria-hidden="true" /></template>
                                <template #decrementbuttonicon><Minus :size="12" aria-hidden="true" /></template>
                            </InputNumber>
                            <small v-if="error('date_offset')" class="error-text">{{ error('date_offset') }}</small>
                        </div>
                    </div>
                </fieldset>
                <button class="button" type="submit" :disabled="!canTest">
                    <LoaderCircle v-if="testing" :size="16" class="spin" aria-hidden="true" /><FlaskConical v-else :size="16" aria-hidden="true" />
                    {{ testing ? 'Test wordt uitgevoerd' : 'Test uitvoeren' }}
                </button>
                <p v-if="requestError" class="error-text mt-4" role="alert">{{ requestError }}</p>
            </form>

            <div v-if="result" class="mt-7 min-w-0 border-t border-[var(--line)] pt-5" aria-live="polite">
                <div class="mb-5 flex flex-wrap items-baseline gap-x-5 gap-y-2">
                    <h2 class="text-base font-semibold">{{ result.barcode }}</h2>
                    <span class="tabular-nums"><strong>{{ result.total_labels }}</strong> labels</span>
                    <span class="muted tabular-nums">{{ result.printer_count }} printers</span>
                    <span class="muted">Niet afgedrukt</span>
                </div>
                <div v-if="printerSummary.length" class="mb-5 flex flex-wrap gap-4">
                    <span v-for="printer in printerSummary" :key="printer.id" class="text-sm"><strong>{{ printer.name }}</strong><span class="muted">: {{ printer.copies }} labels</span></span>
                </div>
                <p v-if="!result.labels.length" class="muted mb-5">Geen labels geselecteerd.</p>
                <div v-for="(label, index) in result.labels" :key="index" class="mb-5 min-w-0 border-b border-[var(--line)] pb-4">
                    <div class="mb-2 flex flex-wrap justify-between gap-2 text-sm">
                        <strong class="break-words">{{ label.label_name }}</strong>
                        <span class="muted">Regel {{ label.line }} / {{ label.copies }} exemplaren</span>
                    </div>
                    <div class="mb-2 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                        <span class="break-words">{{ label.printer_name }}</span>
                        <span v-if="label.sample_analysis_id" class="muted">Analyse #{{ label.sample_analysis_id }}</span>
                    </div>
                    <pre class="zpl-output max-h-48 overflow-auto p-3 text-xs"><code>{{ label.zpl }}</code></pre>
                </div>
                <details>
                    <summary class="cursor-pointer text-sm font-semibold">Uitvoering ({{ result.trace.length }} regels)</summary>
                    <ol class="mt-3 max-h-64 overflow-auto text-xs">
                        <li v-for="(step, index) in result.trace" :key="index" class="flex gap-3 py-1">
                            <span class="muted w-10 shrink-0 text-right tabular-nums">{{ step.line }}</span>
                            <span class="min-w-0 break-words"><code>{{ step.command }}</code><span v-if="step.message" class="ml-2 muted">{{ step.message }}</span></span>
                        </li>
                    </ol>
                </details>
            </div>
        </section>
    </div>
</template>

<style scoped>
.profile-editor { --p-primary-color:var(--accent); --p-select-background:var(--surface); --p-select-color:var(--ink); --p-select-border-color:var(--line); --p-select-focus-border-color:var(--accent); --p-select-border-radius:2px; --p-select-overlay-background:var(--surface); --p-select-overlay-color:var(--ink); --p-select-option-selected-background:var(--accent-faint); --p-select-option-selected-color:var(--accent); }
.profile-editor :deep(.p-select), .profile-editor :deep(.p-select-overlay) { min-width:0; font:inherit; }
.profile-editor :deep(.p-select-label) { padding:9px 10px; font:inherit; }
.profile-editor :deep(.p-select-option) { min-height:36px; white-space:normal; overflow-wrap:anywhere; }
.profile-editor :deep(.p-inputnumber-button) { width:28px; color:var(--accent); background:var(--surface-alt); border-color:var(--line); }
.script-editor { min-height:360px; width:100%; max-width:100%; resize:vertical; padding:12px; font:13px/1.7 'Courier New', monospace; tab-size:4; color:var(--ink); background:var(--surface-alt); border:1px solid var(--line); border-radius:2px; }
.script-editor:focus { outline:2px solid var(--accent); outline-offset:1px; }
.zpl-output { color:var(--ink); background:var(--surface-alt); border:1px solid var(--line); border-radius:2px; white-space:pre-wrap; overflow-wrap:anywhere; }
</style>