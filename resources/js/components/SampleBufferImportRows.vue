<script setup>
import { Trash2 } from '@lucide/vue';
import { isValidDate } from '../stores/sampleBufferImportStore';

defineProps({
    rows: { type: Array, required: true },
    metaColumns: { type: Array, required: true },
    productGroups: { type: Array, required: true },
    issues: { type: Object, required: true },
});
defineEmits(['remove']);
</script>

<template>
    <div class="table-scroll import-table-wrap">
        <table class="data-table import-table">
            <thead>
                <tr>
                    <th scope="col">Regel</th>
                    <th scope="col">Monsternamedatum</th>
                    <th scope="col">Projectnaam</th>
                    <th scope="col">Monsternaam</th>
                    <th scope="col">Details</th>
                    <th scope="col">THT</th>
                    <th scope="col">THT-datum</th>
                    <th scope="col">Analyses</th>
                    <th v-if="productGroups.length" scope="col">Productgroep</th>
                    <th scope="col">Opmerkingen</th>
                    <th v-for="column in metaColumns" :key="column.name" scope="col">
                        <label class="import-meta-toggle" title="Kolom meenemen in de import"><input v-model="column.include" type="checkbox">{{ column.name }}</label>
                    </th>
                    <th scope="col"><span class="sr-only">Rij verwijderen</span></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in rows" :key="row.key" :class="{ 'has-issue': issues[row.key] }">
                    <td class="import-line">{{ row.line }}</td>
                    <td>
                        <input v-model="row.sampling_date" class="form-control min-h-8 px-2 py-1.5" type="date" min="2000-01-01" max="2099-12-31" :aria-invalid="Boolean(issues[row.key]?.sampling_date)" :aria-label="`Monsternamedatum regel ${row.line}`">
                        <small v-if="issues[row.key]?.sampling_date" class="error-text">{{ issues[row.key].sampling_date }}</small>
                        <small v-if="row.sampling_date_source && !isValidDate(row.sampling_date)">CSV: {{ row.sampling_date_source }}</small>
                    </td>
                    <td><input v-model="row.project_name" class="form-control min-h-8 px-2 py-1.5" type="text" maxlength="128" :aria-label="`Projectnaam regel ${row.line}`"></td>
                    <td>
                        <input v-model="row.sample_name" class="form-control min-h-8 px-2 py-1.5" type="text" maxlength="2000" :aria-invalid="Boolean(issues[row.key]?.sample_name)" :aria-label="`Monsternaam regel ${row.line}`">
                        <small v-if="issues[row.key]?.sample_name" class="error-text">{{ issues[row.key].sample_name }}</small>
                    </td>
                    <td><input v-model="row.sample_details" class="form-control min-h-8 px-2 py-1.5" type="text" maxlength="2000" :aria-label="`Details regel ${row.line}`"></td>
                    <td class="import-check"><input v-model="row.tht" type="checkbox" :aria-label="`THT-onderzoek regel ${row.line}`"></td>
                    <td>
                        <input v-model="row.tht_date" class="form-control min-h-8 px-2 py-1.5" type="date" min="2000-01-01" max="2099-12-31" :disabled="!row.tht" :aria-invalid="Boolean(issues[row.key]?.tht_date)" :aria-label="`THT-datum regel ${row.line}`">
                        <small v-if="issues[row.key]?.tht_date" class="error-text">{{ issues[row.key].tht_date }}</small>
                        <small v-else-if="row.tht && row.tht_date_source && !isValidDate(row.tht_date)">CSV: {{ row.tht_date_source }}</small>
                    </td>
                    <td><input v-model="row.analyses_selected" class="form-control min-h-8 px-2 py-1.5" type="text" maxlength="2000" :aria-label="`Analyses regel ${row.line}`"></td>
                    <td v-if="productGroups.length">
                        <select v-model="row.product_group" class="form-control min-h-8 px-2 py-1.5" :aria-invalid="row.product_group_alert" :aria-label="`Productgroep regel ${row.line}`" @change="row.product_group_alert = false">
                            <option :value="null">Geen productgroep</option>
                            <option v-for="group in productGroups" :key="group.id" :value="group.id">{{ group.name }}{{ group.active ? '' : ' [Inactief voor klant]' }}</option>
                        </select>
                        <small v-if="row.product_group_alert" class="error-text">Klant invoer: {{ row.product_group_text || '(leeg)' }}</small>
                    </td>
                    <td><input v-model="row.misc_directions" class="form-control min-h-8 px-2 py-1.5" type="text" maxlength="2000" :aria-label="`Opmerkingen regel ${row.line}`"></td>
                    <td v-for="(column, index) in metaColumns" :key="column.name">
                        <input v-model="row.meta[index]" class="form-control min-h-8 px-2 py-1.5" type="text" maxlength="2000" :disabled="!column.include" :aria-label="`${column.name} regel ${row.line}`">
                    </td>
                    <td class="import-actions"><button class="icon-button" type="button" :title="`Regel ${row.line} verwijderen`" :aria-label="`Regel ${row.line} verwijderen`" @click="$emit('remove', row.key)"><Trash2 :size="16" aria-hidden="true" /></button></td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
