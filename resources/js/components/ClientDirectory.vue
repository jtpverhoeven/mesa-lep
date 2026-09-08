<script setup>
import { computed, ref } from 'vue';
import { Pencil, Plus, Search, UserRoundX } from '@lucide/vue';

const props = defineProps({
    clients: { type: Array, default: () => [] },
    createUrl: { type: String, required: true },
    editUrl: { type: String, required: true },
    destroyUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    canManage: { type: Boolean, default: false },
});

const search = ref('');
const status = ref('active');
const deletingId = ref(null);
const error = ref('');
const localClients = ref(props.clients);

const filteredClients = computed(() => {
    const query = search.value.trim().toLocaleLowerCase();

    return localClients.value.filter((client) => {
        const statusMatches = status.value === 'all'
            || (status.value === 'active' && client.active)
            || (status.value === 'inactive' && !client.active);
        const textMatches = !query || [client.name, client.place, client.email, client.debit_number]
            .filter(Boolean)
            .some((value) => value.toLocaleLowerCase().includes(query));

        return statusMatches && textMatches;
    });
});

function editUrl(client) {
    return props.editUrl.replace('__CLIENT__', client.id);
}

async function deactivate(client) {
    if (!window.confirm(`Klant "${client.name}" deactiveren?`)) return;

    deletingId.value = client.id;
    error.value = '';

    try {
        const response = await fetch(props.destroyUrl.replace('__CLIENT__', client.id), {
            method: 'DELETE',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': props.csrf },
        });

        if (!response.ok) throw new Error();

        localClients.value = localClients.value.map((currentClient) => currentClient.id === client.id
            ? { ...currentClient, active: false }
            : currentClient);
    } catch {
        error.value = 'De klant kon niet worden gedeactiveerd.';
    } finally {
        deletingId.value = null;
    }
}
</script>

<template>
    <div class="client-directory">
        <p v-if="error" class="notice error" role="alert">{{ error }}</p>
        <div class="directory-toolbar">
            <label class="directory-search">
                <Search :size="16" aria-hidden="true" />
                <span class="sr-only">Klanten zoeken</span>
                <input v-model="search" type="search" placeholder="Zoek naam, plaats, e-mail of debiteurnummer">
            </label>
            <div class="directory-status" role="group" aria-label="Klantstatus">
                <button v-for="option in [{ value: 'active', label: 'Actief' }, { value: 'inactive', label: 'Inactief' }, { value: 'all', label: 'Alle' }]" :key="option.value" class="button" :class="{ primary: status === option.value }" type="button" @click="status = option.value">{{ option.label }}</button>
            </div>
            <a v-if="canManage" class="button primary" :href="createUrl"><Plus :size="16" aria-hidden="true" /> Klant toevoegen</a>
        </div>

        <div class="table-scroll">
            <table class="data-table client-directory-table">
                <thead><tr><th>Naam</th><th>Plaats</th><th>Contact</th><th>Categorieen</th><th>Status</th><th v-if="canManage">Acties</th></tr></thead>
                <tbody>
                    <tr v-for="client in filteredClients" :key="client.id" :class="{ muted: !client.active }">
                        <td><strong>{{ client.name }}</strong><small v-if="client.debit_number">Debiteur: {{ client.debit_number }}</small></td>
                        <td>{{ [client.street_name, client.street_number, client.postal_code, client.place].filter(Boolean).join(', ') || '-' }}</td>
                        <td><span>{{ client.email || '-' }}</span><small v-if="client.telephone || client.cellphone">{{ client.telephone || client.cellphone }}</small></td>
                        <td><span v-if="client.categories.length" class="client-category-list"><span v-for="category in client.categories" :key="category.id">{{ category.name }}</span></span><span v-else>-</span></td>
                        <td><span class="status-label" :class="{ inactive: !client.active }">{{ client.active ? 'Actief' : 'Gedeactiveerd' }}</span></td>
                        <td v-if="canManage"><a class="icon-button" :href="editUrl(client)" title="Klant bewerken" :aria-label="`Klant ${client.name} bewerken`"><Pencil :size="16" aria-hidden="true" /></a><button v-if="client.active" class="icon-button danger" type="button" title="Klant deactiveren" :aria-label="`Klant ${client.name} deactiveren`" :disabled="deletingId === client.id" @click="deactivate(client)"><UserRoundX :size="16" aria-hidden="true" /></button></td>
                    </tr>
                    <tr v-if="!filteredClients.length"><td :colspan="canManage ? 6 : 5" class="empty-state">Geen klanten gevonden.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>