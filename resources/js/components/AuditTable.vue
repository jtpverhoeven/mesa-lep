<script setup>
import Column from 'openvue/column';
import DataTable from 'openvue/datatable';
import AuditFlaggedValue from './AuditFlaggedValue.vue';

// Columns: { field, header, type?: 'flag' | 'material', each?: 'item field' for list fields, flag?: true for flagged list items }.
defineProps({
    rows: { type: Array, required: true },
    columns: { type: Array, required: true },
    emptyText: { type: String, default: 'Geen gegevens.' },
});

function read(data, path) {
    return path.split('.').reduce((value, key) => value?.[key], data);
}

function show(value) {
    return value === null || value === undefined || value === '' ? '-' : value;
}
</script>

<template>
    <div class="table-scroll">
        <DataTable :value="rows" size="small" show-gridlines table-class="audit-table">
            <Column v-for="column in columns" :key="column.header + column.field" :field="column.field" :header="column.header" :header-class="column.headerClass">
                <template #body="{ data }">
                    <template v-if="column.each">
                        <div v-for="(item, index) in read(data, column.field)" :key="index">
                            <AuditFlaggedValue v-if="column.flag" :value="read(item, column.each)" />
                            <template v-else>{{ show(read(item, column.each)) }}</template>
                        </div>
                        <span v-if="!read(data, column.field)?.length">-</span>
                    </template>
                    <AuditFlaggedValue v-else-if="column.type === 'flag' || column.type === 'material'" :value="read(data, column.field)" :material="column.type === 'material'" :unit="column.unit ? read(data, column.unit) : ''" />
                    <template v-else>{{ show(read(data, column.field)) }}</template>
                </template>
            </Column>
            <template #empty><span class="empty-state">{{ emptyText }}</span></template>
        </DataTable>
    </div>
</template>
