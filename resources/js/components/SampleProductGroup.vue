<script setup>
import { ref, useId, watch } from 'vue';
import { LoaderCircle, Pencil, Save } from '@lucide/vue';
import AppDialog from './AppDialog.vue';

const props = defineProps({
    sample: { type: Object, required: true },
    productGroup: { type: Object, default: null },
    groupsEndpoint: { type: String, required: true },
    updateEndpoint: { type: String, required: true },
    readOnly: { type: Boolean, default: false },
});
const emit = defineEmits(['updated']);
const formId = `sample-product-group-form-${useId()}`;
const groups = ref([]);
const selectedId = ref(props.productGroup?.portal_id ?? props.sample.portal_product_group_id ?? null);
const dialogVisible = ref(false);
const loading = ref(true);
const saving = ref(false);
const error = ref('');

function endpoint(template) {
    return template.replace('__SAMPLE__', String(props.sample.id));
}

async function load() {
    loading.value = true;
    error.value = '';
    try {
        const response = await fetch(endpoint(props.groupsEndpoint), { headers: { Accept: 'application/json' } });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Productgroepen konden niet worden geladen.');
        groups.value = result.data;
    } catch (exception) {
        error.value = exception.message;
    } finally {
        loading.value = false;
    }
}

async function open() {
    dialogVisible.value = true;
    selectedId.value = props.productGroup?.portal_id ?? props.sample.portal_product_group_id ?? null;
    if (!groups.value.length) await load();
}

async function save() {
    if (!selectedId.value || saving.value || props.readOnly) return;
    saving.value = true;
    error.value = '';
    try {
        const response = await fetch(endpoint(props.updateEndpoint), {
            method: 'PATCH',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
            body: JSON.stringify({ product_group_id: selectedId.value }),
        });
        const result = await response.json();
        if (!response.ok) throw new Error(Object.values(result.errors ?? {}).flat().join(' ') || result.message || 'Productgroep kon niet worden opgeslagen.');
        emit('updated', result.data);
        dialogVisible.value = false;
    } catch (exception) {
        error.value = exception.message;
    } finally {
        saving.value = false;
    }
}

watch(() => props.sample.id, () => {
    groups.value = [];
    selectedId.value = props.productGroup?.portal_id ?? props.sample.portal_product_group_id ?? null;
    dialogVisible.value = false;
    error.value = '';
});
</script>

<template>
    <div class="product-group-editor">
        <dl class="lookup-details"><dt>Houdbaarheidscode</dt><dd>{{ sample.tht_code || '-' }}</dd><dt>Huidige productgroep</dt><dd>{{ productGroup?.name ?? 'Onbekend' }}</dd></dl>
        <div v-if="!readOnly" class="product-group-toolbar"><button class="button primary" type="button" @click="open"><Pencil :size="15" />Productgroep wijzigen</button></div>
        <AppDialog v-model:visible="dialogVisible" title="Productgroep wijzigen" width="520px" :dismissable-mask="!saving" :close-on-escape="!saving" :closable="!saving">
            <form :id="formId" class="product-group-form" @submit.prevent="save">
                <p v-if="error" class="error-text" role="alert">{{ error }}</p>
                <label for="sample-product-group">Productgroep</label>
                <select id="sample-product-group" v-model="selectedId" :disabled="loading || saving">
                    <option :value="null">Selecteer een productgroep</option>
                    <option v-for="group in groups" :key="group.portal_id" :value="group.portal_id">{{ group.name }}{{ !Number(group.visible) ? ' [Inactief voor klant]' : '' }}</option>
                </select>
            </form>
            <template #footer><button class="button" type="button" :disabled="saving" @click="dialogVisible = false">Annuleren</button><button class="button primary" type="submit" :form="formId" :disabled="loading || saving || selectedId === null"><LoaderCircle v-if="saving" class="spin" :size="15" /><Save v-else :size="15" />Opslaan</button></template>
        </AppDialog>
    </div>
</template>

<style>
.product-group-editor { min-width:0; }
.product-group-toolbar { display:flex; justify-content:flex-end; padding-bottom:5px; }
.product-group-toolbar .button { min-height:27px; padding:3px 7px; font-size:11px; }
.product-group-form { display:grid; gap:13px; }
.product-group-form label { display:grid; gap:5px; color:var(--muted); font-size:11px; }
.product-group-form select { width:100%; min-width:0; padding:7px 8px; border:1px solid #9eabb2; border-radius:2px; background:var(--surface); color:var(--ink); font:inherit; }
</style>