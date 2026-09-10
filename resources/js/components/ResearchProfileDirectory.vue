<script setup>
import { onMounted, ref } from 'vue';
import { CopyPlus, ListChecks, LoaderCircle, Pencil, Search, Trash2 } from '@lucide/vue';
import { useResearchProfileStore } from '../stores/researchProfileStore';

const props = defineProps({ endpoints: { type: Object, required: true } });
const store = useResearchProfileStore();
const query = ref('');
let timer;

function search() {
    window.clearTimeout(timer);
    timer = window.setTimeout(() => store.loadProfiles(query.value), 250);
}

onMounted(() => {
    store.configure(props.endpoints);
    store.loadProfiles();
});
</script>

<template>
    <div>
        <div v-if="store.message" class="notice success" role="status">{{ store.message }}</div>
        <div v-if="store.error" class="notice error" role="alert">{{ store.error }}</div>
        <div class="directory-toolbar">
            <label class="directory-search"><Search :size="16" aria-hidden="true" /><span class="sr-only">Profielen zoeken</span><input v-model="query" type="search" placeholder="Zoek op profielnaam" @input="search"></label>
            <a class="button" :href="endpoints.bulkPage"><ListChecks :size="16" />Bulkwijziging</a>
            <a class="button primary" :href="endpoints.create"><CopyPlus :size="16" />Profiel toevoegen</a>
        </div>
        <div class="table-scroll">
            <table class="data-table profile-directory-table">
                <thead><tr><th>ID</th><th>Naam</th><th>Bereik</th><th>Portal</th><th>LIMS</th><th>Acties</th></tr></thead>
                <tbody>
                    <tr v-for="profile in store.profiles" :key="profile.id">
                        <td>{{ profile.id }}</td><td><strong>{{ profile.name }}</strong></td><td>{{ profile.scope }}</td>
                        <td>{{ profile.portal_visible ? 'Ja' : 'Nee' }}</td><td>{{ profile.lims_visible ? 'Ja' : 'Nee' }}</td>
                        <td><a class="icon-button" :href="endpoints.edit.replace('__ID__', profile.id)" title="Bewerken"><Pencil :size="16" /></a><button class="icon-button danger" type="button" title="Verwijderen" @click="store.remove(profile.id)"><Trash2 :size="16" /></button></td>
                    </tr>
                    <tr v-if="store.loading"><td colspan="6" class="empty-state"><LoaderCircle class="spin" :size="18" /> Profielen laden</td></tr>
                    <tr v-else-if="!store.profiles.length"><td colspan="6" class="empty-state">Geen onderzoeksprofielen gevonden.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>