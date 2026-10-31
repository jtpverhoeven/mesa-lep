<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { RefreshCw, Trash2 } from '@lucide/vue';
import Button from 'openvue/button';
import { getEcho } from '../echo.js';
import AppConfirmDialog from './AppConfirmDialog.vue';

const props = defineProps({
    dataUrl: { type: String, required: true },
    destroyUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});

const labels = { sample: 'Monster', project: 'Project', export: 'Export', extern: 'Externe export' };
const keyrings = ref([]);
const loaded = ref(false);
const loading = ref(false);
const loadError = ref('');
const realtimeError = ref('');
const realtimeConnected = ref(false);
const confirmationVisible = ref(false);
const pendingDeletion = ref(null);
const deleting = ref(false);
const deleteError = ref('');
let realtimeClient = null;
let requestVersion = 0;

const deletionMessage = computed(() => {
    if (!pendingDeletion.value) return '';

    return `Vergrendeling voor ${resourceLabel(pendingDeletion.value)} door ${pendingDeletion.value.holder_name || 'een onbekende gebruiker'} verwijderen?`;
});

function resourceLabel(keyring) {
    return `${labels[keyring.type] || keyring.type} #${keyring.type_id}`;
}

function formatExpiry(value) {
    return new Intl.DateTimeFormat('nl-NL', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value));
}

async function loadKeyrings() {
    const currentRequest = ++requestVersion;
    loading.value = true;
    loadError.value = '';

    try {
        const response = await fetch(props.dataUrl, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error();

        const result = await response.json();
        if (!Array.isArray(result.data)) throw new Error();

        if (currentRequest === requestVersion) {
            keyrings.value = result.data;
            loaded.value = true;
        }
    } catch {
        if (currentRequest === requestVersion) loadError.value = 'De vergrendelingen konden niet worden geladen.';
    } finally {
        if (currentRequest === requestVersion) loading.value = false;
    }
}

function confirmDeletion(keyring) {
    pendingDeletion.value = keyring;
    deleteError.value = '';
    confirmationVisible.value = true;
}

async function forceDelete() {
    if (!pendingDeletion.value || deleting.value) return;

    const keyring = pendingDeletion.value;
    deleting.value = true;
    deleteError.value = '';

    try {
        const response = await fetch(props.destroyUrl.replace('__KEYRING__', String(keyring.id)), {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': props.csrf,
            },
        });
        if (!response.ok) throw new Error();

        keyrings.value = keyrings.value.filter((entry) => entry.id !== keyring.id);
        confirmationVisible.value = false;
        pendingDeletion.value = null;
        void loadKeyrings();
    } catch {
        deleteError.value = 'De vergrendeling kon niet worden verwijderd. Vernieuw de lijst en probeer opnieuw.';
    } finally {
        deleting.value = false;
    }
}

onMounted(() => {
    void loadKeyrings();

    try {
        realtimeClient = getEcho();
        realtimeClient.private('keyrings.admin')
            .listen('.keyring.changed', () => void loadKeyrings())
            .subscribed(() => {
                realtimeConnected.value = true;
                realtimeError.value = '';
                void loadKeyrings();
            })
            .error(() => {
                realtimeConnected.value = false;
                realtimeError.value = 'De liveverbinding met Reverb is niet beschikbaar.';
            });
    } catch {
        realtimeConnected.value = false;
        realtimeError.value = 'De liveverbinding met Reverb kon niet worden gestart.';
    }
});

onUnmounted(() => {
    if (realtimeClient) realtimeClient.leave('keyrings.admin');
    realtimeConnected.value = false;
});
</script>

<template>
    <div>
        <div class="page-heading">
            <div>
                <div class="eyebrow">Beheer / Systeembeheer</div>
                <h1>Vergrendelingen</h1>                
            </div>
            <Button type="button" label="Vernieuwen" severity="secondary" :loading="loading" @click="loadKeyrings">
                <template #icon><RefreshCw :size="15" aria-hidden="true" /></template>
            </Button>
        </div>

        <p v-if="realtimeError" class="notice error" role="alert">{{ realtimeError }}</p>
        <!-- <p v-else-if="realtimeConnected" class="muted" role="status">Live updates via Reverb verbonden.</p>
        <p v-else class="muted" role="status">Verbinding met Reverb wordt gestart...</p> -->
        <p v-if="loadError" class="notice error" role="alert">{{ loadError }}</p>
        <p v-if="deleteError" class="notice error" role="alert">{{ deleteError }}</p>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Gebruiker</th>
                        <th>Verloopt</th>
                        <th>Actie</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading && !loaded">
                        <td colspan="4">Vergrendelingen laden...</td>
                    </tr>
                    <tr v-else-if="loadError && !loaded">
                        <td colspan="4">De lijst is niet beschikbaar.</td>
                    </tr>
                    <tr v-else-if="!keyrings.length">
                        <td colspan="4">Er zijn geen vergrendelingen uitgegeven.</td>
                    </tr>
                    <tr v-for="keyring in keyrings" :key="keyring.id">
                        <td>{{ resourceLabel(keyring) }}</td>
                        <td>{{ keyring.holder_name || `Onbekende gebruiker (#${keyring.holder_id})` }}</td>
                        <td>{{ keyring.expired ? 'Verlopen - ' : '' }}{{ formatExpiry(keyring.expires_at) }}</td>
                        <td>
                            <Button type="button" label="Geforceerd verwijderen" severity="danger" variant="outlined" size="small" :disabled="deleting" @click="confirmDeletion(keyring)">
                                <template #icon><Trash2 :size="14" aria-hidden="true" /></template>
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppConfirmDialog v-model:visible="confirmationVisible" title="Vergrendeling geforceerd verwijderen" danger :message="deletionMessage" confirm-label="Verwijderen" :loading="deleting" :disabled="!pendingDeletion" :error="deleteError" @confirm="forceDelete">
            <template #confirm-icon><Trash2 :size="15" aria-hidden="true" /></template>
        </AppConfirmDialog>
    </div>
</template>
