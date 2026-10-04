<script setup>
import { computed, nextTick, ref } from 'vue';
import { CheckCheck, Copy, Filter, RotateCcw, Search, Trash2 } from '@lucide/vue';
import AppDialog from './AppDialog.vue';

const props = defineProps({
    clients: { type: Array, default: () => [] },
    selectedClientIds: { type: Array, default: () => [] },
    copySources: { type: Array, default: () => [] },
    formAction: { type: String, required: true },
    copyAction: { type: String, required: true },
    csrf: { type: String, required: true },
});

const search = ref('');
const availabilityFilter = ref('all');
const selectedClientIds = ref(new Set(props.selectedClientIds.map(Number)));
const replaceAll = ref(false);
const availabilityForm = ref(null);
const copyForm = ref(null);
const copyFrom = ref('');
const pendingOperation = ref(null);
const saving = ref(false);
const error = ref('');
const filteredClients = computed(() => {
    const query = search.value.trim().toLocaleLowerCase('nl-NL');

    return props.clients.filter((client) => {
        const matchesSearch = !query || [client.name, client.reference, client.categories]
            .some((value) => String(value ?? '').toLocaleLowerCase('nl-NL').includes(query));
        const isAvailable = selected(client.id);

        return matchesSearch
            && (availabilityFilter.value === 'all'
                || (availabilityFilter.value === 'available' && isAvailable)
                || (availabilityFilter.value === 'unavailable' && !isAvailable));
    });
});

function selected(clientId) {
    return selectedClientIds.value.has(Number(clientId));
}

function toggleClient(clientId, checked) {
    const next = new Set(selectedClientIds.value);
    checked ? next.add(Number(clientId)) : next.delete(Number(clientId));
    selectedClientIds.value = next;
}

function saveAvailability() {
    submitAvailability(false);
}

function showAll() {
    availabilityFilter.value = 'all';
    search.value = '';
}

function requestBulkOperation(type) {
    pendingOperation.value = type;
}

function requestCopy() {
    if (!copyForm.value?.reportValidity()) return;

    pendingOperation.value = 'copy';
}

async function confirmOperation() {
    const operation = pendingOperation.value;
    pendingOperation.value = null;

    if (operation === 'add') {
        selectedClientIds.value = new Set(props.clients.map((client) => Number(client.id)));
        await submitAvailability(true);
    }

    if (operation === 'remove') {
        selectedClientIds.value = new Set();
        await submitAvailability(true);
    }

    if (operation === 'copy') copyForm.value?.submit();
}

async function submitAvailability(replace) {
    if (saving.value) return;

    replaceAll.value = replace;
    error.value = '';
    await nextTick();

    const form = availabilityForm.value;
    if (!form) return;

    saving.value = true;

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': props.csrf,
            },
        });

        if (!response.ok) {
            const payload = await response.json().catch(() => null);
            error.value = payload?.message ?? 'De klantbeschikbaarheid kon niet worden opgeslagen.';

            return;
        }

        window.location.reload();
    } catch {
        error.value = 'De klantbeschikbaarheid kon niet worden opgeslagen. Controleer de netwerkverbinding en probeer opnieuw.';
    } finally {
        saving.value = false;
    }
}

const confirmation = computed(() => {
    if (pendingOperation.value === 'add') {
        return {
            title: 'Toevoegen aan alle klanten',
            message: 'Deze portaalanalyse wordt beschikbaar voor alle actieve klanten.',
            confirm: 'Toevoegen',
        };
    }

    if (pendingOperation.value === 'remove') {
        return {
            title: 'Verwijderen van alle klanten',
            message: 'Alle klantbeschikbaarheid voor deze portaalanalyse wordt verwijderd.',
            confirm: 'Verwijderen',
        };
    }

    return {
        title: 'Klantbeschikbaarheid kopieren',
        message: 'De huidige klantbeschikbaarheid wordt volledig vervangen door die van de gekozen portaalanalyse.',
        confirm: 'Kopieren',
    };
});
</script>

<template>
    <div class="portal-assay-clients-layout">
        <form ref="availabilityForm" class="clients-panel" :action="formAction" method="post">
            <input type="hidden" name="_token" :value="csrf">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="replace_all" :value="replaceAll ? 1 : 0">
            <input v-for="clientId in [...selectedClientIds]" :key="`selected-${clientId}`" type="hidden" name="client_ids[]" :value="clientId">
            <div class="client-toolbar"><label class="directory-search"><Search :size="16" aria-hidden="true" /><span class="sr-only">Klanten zoeken</span><input v-model="search" type="search" placeholder="Zoek klanten" autocomplete="off"></label><span class="client-count">{{ filteredClients.length }} van {{ clients.length }} klanten</span></div>
            <p v-if="error" class="error-text" role="alert">{{ error }}</p>
            <div class="table-scroll"><table class="data-table"><thead><tr><th scope="col">Beschikbaar</th><th scope="col">Klant</th><th scope="col">Referentie</th><th scope="col">Categorieën</th></tr></thead><tbody><tr v-for="client in filteredClients" :key="client.id"><td><input :id="`client-${client.id}`" type="checkbox" :checked="selected(client.id)" @change="toggleClient(client.id, $event.target.checked)"></td><td><label :for="`client-${client.id}`"><strong>{{ client.name }}</strong></label></td><td>{{ client.reference || '-' }}</td><td>{{ client.categories || '-' }}</td></tr><tr v-if="!filteredClients.length"><td colspan="4" class="empty-state">Geen klanten gevonden.</td></tr></tbody></table></div>
        </form>

        <aside class="operations-panel" aria-label="Tabelacties">
            <section class="operation-group"><h2>Tabel tonen</h2><div class="operation-buttons"><button class="button secondary" :class="{ selected: availabilityFilter === 'available' }" type="button" @click="availabilityFilter = 'available'"><Filter :size="15" />Met beschikbaarheid</button><button class="button secondary" :class="{ selected: availabilityFilter === 'unavailable' }" type="button" @click="availabilityFilter = 'unavailable'"><Filter :size="15" />Zonder beschikbaarheid</button><button class="button secondary" :class="{ selected: availabilityFilter === 'all' }" type="button" @click="showAll"><RotateCcw :size="15" />Toon alles</button></div></section>
            <section class="operation-group"><h2>Bulkoperaties</h2><div class="operation-buttons"><button class="button secondary" type="button" :disabled="saving" @click="requestBulkOperation('add')"><CheckCheck :size="15" />Toevoegen aan alle klanten</button><button class="button danger" type="button" :disabled="saving" @click="requestBulkOperation('remove')"><Trash2 :size="15" />Verwijderen van alle klanten</button></div></section>
            <section class="operation-group"><h2>Kopieren</h2><form ref="copyForm" class="copy-form" :action="copyAction" method="post"><input type="hidden" name="_token" :value="csrf"><label for="copy-source">Van portaalanalyse</label><select id="copy-source" v-model="copyFrom" name="copy_from" required><option disabled value="">Kies een portaalanalyse</option><option v-for="source in copySources" :key="source.id" :value="String(source.id)">{{ source.common_name }}</option></select><button class="button secondary" type="button" :disabled="saving" @click="requestCopy"><Copy :size="15" />Klantbeschikbaarheid kopieren</button></form></section>
            <button class="button primary save-button" type="button" :disabled="saving" @click="saveAvailability">{{ saving ? 'Opslaan...' : 'Beschikbaarheid opslaan' }}</button>
        </aside>
    </div>

    <AppDialog :visible="pendingOperation !== null" :title="confirmation.title" width="440px" @update:visible="pendingOperation = null"><p class="confirmation-message">{{ confirmation.message }}</p><template #footer><button class="button secondary" type="button" @click="pendingOperation = null">Annuleren</button><button class="button primary" type="button" @click="confirmOperation">{{ confirmation.confirm }}</button></template></AppDialog>
</template>

<style scoped>
.portal-assay-clients-layout { display:grid; grid-template-columns:minmax(0, 1fr) minmax(240px, 300px); align-items:start; gap:20px; }
.clients-panel { min-width:0; }
.client-toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px; flex-wrap:wrap; }
.client-count { color:var(--muted); font-size:12px; }
.operations-panel { display:grid; gap:16px; position:sticky; top:12px; padding:14px; border:1px solid var(--line); background:var(--surface-alt); }
.operation-group { display:grid; gap:8px; }
.operation-group + .operation-group { padding-top:16px; border-top:1px solid var(--line); }
.operation-group h2 { margin:0; font-size:14px; }
.operation-buttons, .copy-form { display:grid; gap:7px; }
.operation-buttons .button, .copy-form .button, .save-button { justify-content:flex-start; }
.operation-buttons .button.selected { border-color:var(--accent); background:var(--accent-faint); color:var(--accent); }
.copy-form label { font-size:12px; color:var(--muted); }
.copy-form select { width:100%; min-width:0; }
.save-button { justify-content:center; }
.confirmation-message { margin:0; line-height:1.5; }
@media (max-width:850px) { .portal-assay-clients-layout { grid-template-columns:minmax(0, 1fr); } .operations-panel { position:static; grid-template-columns:repeat(2, minmax(0, 1fr)); } .save-button { grid-column:1 / -1; } }
@media (max-width:560px) { .operations-panel { grid-template-columns:minmax(0, 1fr); } }
</style>