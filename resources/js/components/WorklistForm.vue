<script setup>
import { computed, reactive, ref } from 'vue';
import { ArrowDown, ArrowUp, LoaderCircle, Plus, Save, Trash2 } from '@lucide/vue';
import InputText from 'openvue/inputtext';
import MultiSelect from 'openvue/multiselect';
import SelectButton from 'openvue/selectbutton';
import Textarea from 'openvue/textarea';
import HtmlEditor from './HtmlEditor.vue';

const props = defineProps({
    initial: { type: Object, required: true },
    assays: { type: Array, required: true },
    errors: { type: Object, default: () => ({}) },
    actionUrl: { type: String, required: true },
    cancelUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    updating: { type: Boolean, default: false },
});

const form = reactive({
    title: props.initial.title ?? '',
    subtitle: props.initial.subtitle ?? '',
    footer_text: props.initial.footer_text ?? '',
    no_columns: props.initial.no_columns ?? 1,
    orientation: props.initial.orientation ?? 'P',
    analyses: (props.initial.analyses ?? []).map(Number),
    columns: Object.values(props.initial.columns ?? {}).map((column) => ({
        name: column.name ?? '',
        group: column.group ?? '',
        tick_on: (column.tick_on ?? []).map(Number),
    })),
    extra_rows: props.initial.extra_rows ?? '',
    extra_pages: props.initial.extra_pages ?? '',
});
const submitting = ref(false);
const canSave = computed(() => form.title.trim() !== '');
const columnCounts = [{ id: 1, name: '1 kolom' }, { id: 2, name: '2 kolommen' }];
const orientations = [{ id: 'P', name: 'Staand' }, { id: 'L', name: 'Liggend' }];

function error(key) {
    return props.errors[key]?.[0] ?? '';
}

function moveColumn(index, step) {
    const target = index + step;
    if (target < 0 || target >= form.columns.length) return;
    form.columns.splice(target, 0, ...form.columns.splice(index, 1));
}

function submit(event) {
    if (!canSave.value || submitting.value) {
        event.preventDefault();
        return;
    }
    submitting.value = true;
}
</script>

<template>
    <form class="grid min-w-0 grid-cols-[minmax(0,1fr)] gap-3" method="POST" :action="actionUrl" @submit="submit">
        <input type="hidden" name="_token" :value="csrf">
        <input v-if="updating" type="hidden" name="_method" value="PUT">
        <input type="hidden" name="no_columns" :value="form.no_columns">
        <input type="hidden" name="orientation" :value="form.orientation">
        <input type="hidden" name="extra_pages" :value="form.extra_pages">
        <input v-for="id in form.analyses" :key="`analysis-${id}`" type="hidden" name="analyses[]" :value="id">
        <template v-for="(column, index) in form.columns" :key="`column-${index}`">
            <input type="hidden" :name="`columns[${index}][name]`" :value="column.name">
            <input type="hidden" :name="`columns[${index}][group]`" :value="column.group">
            <input v-for="id in column.tick_on" :key="id" type="hidden" :name="`columns[${index}][tick_on][]`" :value="id">
        </template>

        <div class="grid gap-3 md:grid-cols-[repeat(3,minmax(0,1fr))]">
            <div class="field">
                <label for="worklist-title">Titel</label>
                <InputText id="worklist-title" v-model="form.title" name="title" maxlength="128" required autofocus :invalid="Boolean(error('title'))" fluid />
                <small v-if="error('title')" class="error-text" role="alert">{{ error('title') }}</small>
            </div>
            <div class="field">
                <label for="worklist-subtitle">Subtitel</label>
                <InputText id="worklist-subtitle" v-model="form.subtitle" name="subtitle" maxlength="128" fluid />
            </div>
            <div class="field">
                <label for="worklist-footer">Voettekst</label>
                <InputText id="worklist-footer" v-model="form.footer_text" name="footer_text" maxlength="128" fluid />
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-[auto_auto_minmax(0,1fr)]">
            <div class="field">
                <label id="worklist-no-columns">Kolommen</label>
                <SelectButton v-model="form.no_columns" :options="columnCounts" option-label="name" option-value="id" :allow-empty="false" aria-labelledby="worklist-no-columns" />
            </div>
            <div class="field">
                <label id="worklist-orientation">Afdrukstand</label>
                <SelectButton v-model="form.orientation" :options="orientations" option-label="name" option-value="id" :allow-empty="false" aria-labelledby="worklist-orientation" />
            </div>
            <div class="field">
                <label for="worklist-analyses">Analyses</label>
                <MultiSelect v-model="form.analyses" input-id="worklist-analyses" :options="assays" option-label="name" option-value="id" display="comma" :max-selected-labels="2" selected-items-label="{0} geselecteerd" :show-toggle-all="false" :virtual-scroller-options="{ itemSize: 36 }" filter filter-placeholder="Zoek analyse" placeholder="Analyses" :invalid="Boolean(error('analyses'))" append-to="self" fluid />
            </div>
        </div>

        <div class="grid gap-1.5">
            <div v-if="form.columns.length" class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,2fr)_auto] items-center gap-2 text-sm font-semibold max-md:hidden">
                <span>Kolom</span><span>Groep</span><span>Aanvinken bij</span><span class="w-[104px]"></span>
            </div>
            <div v-for="(column, index) in form.columns" :key="index" class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,2fr)_auto] items-center gap-2 max-md:grid-cols-1">
                <InputText v-model="column.name" maxlength="128" placeholder="Kolom" :aria-label="`Kolom ${index + 1}`" fluid />
                <InputText v-model="column.group" maxlength="128" placeholder="Groep" :aria-label="`Groep kolom ${index + 1}`" fluid />
                <MultiSelect v-model="column.tick_on" :options="assays" option-label="name" option-value="id" display="comma" :max-selected-labels="2" selected-items-label="{0} geselecteerd" :show-toggle-all="false" :virtual-scroller-options="{ itemSize: 36 }" filter filter-placeholder="Zoek analyse" placeholder="Leeg" :aria-label="`Aanvinken bij kolom ${index + 1}`" append-to="self" fluid />
                <div class="flex items-center gap-1">
                    <button class="icon-button" type="button" title="Omhoog" aria-label="Omhoog" :disabled="index === 0" @click="moveColumn(index, -1)"><ArrowUp :size="16" aria-hidden="true" /></button>
                    <button class="icon-button" type="button" title="Omlaag" aria-label="Omlaag" :disabled="index === form.columns.length - 1" @click="moveColumn(index, 1)"><ArrowDown :size="16" aria-hidden="true" /></button>
                    <button class="icon-button danger" type="button" title="Verwijderen" aria-label="Kolom verwijderen" @click="form.columns.splice(index, 1)"><Trash2 :size="16" aria-hidden="true" /></button>
                </div>
            </div>
            <div><button class="button" type="button" @click="form.columns.push({ name: '', group: '', tick_on: [] })"><Plus :size="16" aria-hidden="true" />Kolom</button></div>
            <small v-if="error('columns')" class="error-text" role="alert">{{ error('columns') }}</small>
        </div>

        <div class="field">
            <label for="worklist-extra-rows">Specifieke rijen</label>
            <Textarea id="worklist-extra-rows" v-model="form.extra_rows" name="extra_rows" rows="3" auto-resize placeholder="Eén rij per regel" :invalid="Boolean(error('extra_rows'))" fluid />
            <small v-if="error('extra_rows')" class="error-text" role="alert">{{ error('extra_rows') }}</small>
        </div>

        <div>
            <HtmlEditor v-model="form.extra_pages" />
            <small v-if="error('extra_pages')" class="error-text" role="alert">{{ error('extra_pages') }}</small>
        </div>

        <div class="form-actions">
            <button class="button primary" type="submit" :disabled="!canSave || submitting">
                <LoaderCircle v-if="submitting" :size="16" class="spin" aria-hidden="true" /><Save v-else :size="16" aria-hidden="true" />Opslaan
            </button>
            <a class="button" :href="cancelUrl">Annuleren</a>
        </div>
    </form>
</template>
