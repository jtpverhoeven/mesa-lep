<script setup>
import { computed, ref } from 'vue';
import { Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import Column from 'openvue/column';
import DataTable from 'openvue/datatable';
import Tab from 'openvue/tab';
import TabList from 'openvue/tablist';
import TabPanel from 'openvue/tabpanel';
import TabPanels from 'openvue/tabpanels';
import Tabs from 'openvue/tabs';
import AppConfirmDialog from './AppConfirmDialog.vue';

const props = defineProps({
    labelEvents: { type: Array, default: () => [] },
    options: { type: Object, required: true },
    initialGroup: { type: String, default: '1' },
    createUrl: { type: String, required: true },
    editUrl: { type: String, required: true },
    destroyUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});

const selectedGroup = ref(Object.hasOwn(props.options.groups, props.initialGroup) ? props.initialGroup : '1');
const groups = Object.entries(props.options.groups).map(([id, name]) => ({ id, name }));
const filters = ref({ global: { value: '', matchMode: 'contains' } });
const deletingId = ref(null);
const deleteDialogVisible = ref(false);
const pendingDeletion = ref(null);
const deletionMessage = computed(() => {
    const rule = pendingDeletion.value?.rule;
    return rule ? `Labelregel #${rule.id} voor "${rule.label_design?.name ?? 'onbekend labelontwerp'}" verwijderen?` : '';
});

function nameFor(key, value) {
    return props.options[key].find((option) => String(option.id) === String(value))?.name ?? `#${value}`;
}

function description(event) {
    if (event.event === 'P') return 'Altijd afdrukken';

    try {
        const condition = JSON.parse(event.event_data);
        const type = props.options.conditions[event.event_group]?.[condition?.type];
        if (!type || !['0', '1'].includes(String(condition.negative)) || condition.inspect_value == null) return 'Ongeldige voorwaarde';
        const operator = String(condition.negative) === '1' ? 'is niet' : 'is';
        const value = condition.inspect_value;

        switch (condition.type) {
            case 'sField': {
                const field = props.options.sampleFields.find((option) => option.name === condition.inspect);
                return `${field?.alias || condition.inspect} ${operator} "${value}"`;
            }
            case 'sClient': return `${type} ${operator} ${nameFor('clients', value)}`;
            case 'mMethod': return `${type} ${operator} ${nameFor('procedures', value)}`;
            case 'mType': return `${type} ${operator} ${props.options.sampleTypes[value] ?? value}`;
            case 'assayIs': return `${type} ${operator} ${String(value).split(',').map((id) => nameFor('assays', id.trim())).join(', ')}`;
            case 'assayTypeIs': return `${type} ${operator} ${nameFor('assayTypes', value)}`;
            default: return 'Ongeldige voorwaarde';
        }
    } catch {
        return 'Ongeldige voorwaarde';
    }
}

const events = computed(() => props.labelEvents.map((event) => ({ ...event, description: description(event) })));
const groupedEvents = computed(() => Object.fromEntries(groups.map((group) => [group.id, events.value.filter((event) => String(event.event_group) === group.id)])));

function url(template, event) {
    return template.replace('__LABEL_EVENT__', String(event.id));
}

function confirmDeletion(event, rule) {
    pendingDeletion.value = { form: event.currentTarget, rule };
    deleteDialogVisible.value = true;
}

function deleteRule() {
    if (!pendingDeletion.value || deletingId.value !== null) return;

    deletingId.value = pendingDeletion.value.rule.id;
    pendingDeletion.value.form.submit();
}
</script>

<template>
    <div class="label-rule-directory">
        <AppConfirmDialog
            v-model:visible="deleteDialogVisible"
            title="Labelregel verwijderen"
            danger
            :message="deletionMessage"
            confirm-label="Verwijderen"
            :loading="deletingId !== null"
            :disabled="!pendingDeletion"
            @confirm="deleteRule"
        >
            <template #confirm-icon><Trash2 :size="15" aria-hidden="true" /></template>
        </AppConfirmDialog>
        <div class="page-heading">
            <div><div class="eyebrow">Beheer / Labels</div><h1>Labelregels</h1></div>
            <a class="button primary" :href="`${createUrl}?group=${selectedGroup}`"><Plus :size="16" aria-hidden="true" />Labelregel toevoegen</a>
        </div>
        <div class="directory-toolbar">
            <label class="directory-search">
                <Search :size="16" aria-hidden="true" />
                <span class="sr-only">Labelregels zoeken</span>
                <input v-model="filters.global.value" type="search" placeholder="Zoek label, printer of voorwaarde" autocomplete="off">
            </label>
        </div>
        <Tabs v-model:value="selectedGroup" class="label-rule-tabs" :unstyled="true">
            <TabList :unstyled="true">
                <Tab v-for="group in groups" :key="group.id" :value="group.id" :unstyled="true">
                    <span>{{ group.name }}</span><span class="muted tabular-nums">{{ groupedEvents[group.id].length }}</span>
                </Tab>
            </TabList>
            <TabPanels :unstyled="true">
                <TabPanel v-for="group in groups" :key="group.id" :value="group.id" :unstyled="true">
                    <div class="table-scroll">
                        <DataTable v-model:filters="filters" :value="groupedEvents[group.id]" data-key="id" :global-filter-fields="['label_design.name', 'printer.name', 'description']" sort-field="id" :sort-order="1" table-class="data-table" :unstyled="true">
                            <Column field="id" header="#" sortable><template #body="{ data }"><span class="muted tabular-nums">{{ data.id }}</span></template></Column>
                            <Column field="description" header="Voorwaarde"><template #body="{ data }"><span class="rule-description">{{ data.description }}</span></template></Column>
                            <Column field="label_design.name" header="Labelontwerp" sortable><template #body="{ data }"><strong v-if="data.label_design">{{ data.label_design.name }}</strong><span v-else class="error-text">Ontwerp ontbreekt</span></template></Column>
                            <Column field="copies" header="Exemplaren" sortable />
                            <Column field="printer.name" header="Printer" sortable><template #body="{ data }"><span v-if="data.printer">{{ data.printer.name }}</span><span v-else class="error-text">Printer ontbreekt</span></template></Column>
                            <Column header="Acties">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-2">
                                        <a class="icon-button" :href="url(editUrl, data)" title="Labelregel bewerken" :aria-label="`Labelregel ${data.id} bewerken`"><Pencil :size="16" aria-hidden="true" /></a>
                                        <form class="inline-form" method="POST" :action="url(destroyUrl, data)" @submit.prevent="confirmDeletion($event, data)">
                                            <input type="hidden" name="_token" :value="csrf"><input type="hidden" name="_method" value="DELETE">
                                            <button class="icon-button danger" type="submit" title="Labelregel verwijderen" :aria-label="`Labelregel ${data.id} verwijderen`" :disabled="deletingId !== null"><Trash2 :size="16" aria-hidden="true" /></button>
                                        </form>
                                    </div>
                                </template>
                            </Column>
                            <template #empty><span class="empty-state">Geen labelregels gevonden.</span></template>
                        </DataTable>
                    </div>
                </TabPanel>
            </TabPanels>
        </Tabs>
    </div>
</template>

<style scoped>
.label-rule-tabs :deep([data-pc-section='tablist']) { display:flex; gap:4px; border-bottom:1px solid var(--line); }
.label-rule-tabs :deep([data-pc-name='tab']) { display:flex; align-items:center; gap:10px; min-height:43px; padding:10px 14px; border-bottom:2px solid transparent; color:var(--muted); font:inherit; font-weight:600; cursor:pointer; }
.label-rule-tabs :deep([data-pc-name='tab'][aria-selected='true']) { border-color:var(--accent); color:var(--accent); background:var(--accent-faint); }
.label-rule-tabs :deep([data-pc-section='activebar']) { display:none; }
.rule-description { display:block; min-width:180px; max-width:440px; white-space:normal; overflow-wrap:anywhere; }
@media (max-width:640px) { .label-rule-tabs :deep([data-pc-name='tab']) { flex:1; justify-content:center; flex-wrap:wrap; gap:4px; padding:8px; text-align:center; } }
</style>