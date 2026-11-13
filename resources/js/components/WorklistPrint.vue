<script setup>
import { computed, ref } from 'vue';
import { Printer } from '@lucide/vue';
import DatePicker from 'openvue/datepicker';
import Select from 'openvue/select';

const props = defineProps({
    worklists: { type: Array, default: () => [] },
    printUrl: { type: String, required: true },
    today: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const toDate = (value) => {
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
};
const worklist = ref('all');
const date = ref(toDate(props.today));
const options = computed(() => [{ id: 'all', title: 'Alle lijsten met monsters' }, ...props.worklists]);
const dateValue = computed(() => date.value instanceof Date && !Number.isNaN(date.value.getTime())
    ? `${date.value.getFullYear()}-${String(date.value.getMonth() + 1).padStart(2, '0')}-${String(date.value.getDate()).padStart(2, '0')}`
    : '');
</script>

<template>
    <div>
        <div class="page-heading"><div><div class="eyebrow">Laboratorium / Afdrukken</div><h1>Werklijsten</h1></div></div>
        <form class="grid max-w-md gap-3" method="GET" :action="printUrl" target="_blank">
            <div class="field">
                <label for="worklist-print-list">Werklijst</label>
                <Select v-model="worklist" input-id="worklist-print-list" :options="options" option-label="title" option-value="id" append-to="self" fluid />
                <input type="hidden" name="worklist" :value="worklist">
            </div>
            <div class="field">
                <label for="worklist-print-date">Inzetdatum</label>
                <DatePicker v-model="date" input-id="worklist-print-date" date-format="dd-mm-yy" :invalid="Boolean(errors.date?.length) || !dateValue" append-to="self" fluid />
                <input type="hidden" name="date" :value="dateValue">
            </div>
            <div><button class="button primary" type="submit" :disabled="!dateValue"><Printer :size="16" aria-hidden="true" />Openen</button></div>
        </form>
    </div>
</template>
