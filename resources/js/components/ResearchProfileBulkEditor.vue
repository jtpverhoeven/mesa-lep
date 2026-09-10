<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { ListChecks, LoaderCircle, Search } from '@lucide/vue';
import { useResearchProfileStore } from '../stores/researchProfileStore';

const props = defineProps({ endpoints: { type: Object, required: true } });
const store = useResearchProfileStore();
const selected = ref([]);
const query = ref('');
const form = reactive({ mutation: 'add', assay: '', replacement_assay: '', dillutions: '', replicates: 0, reference: '', reference_source: null, conf_trip: 0 });
const visibleProfiles = computed(() => store.profiles.filter((profile) => !query.value || profile.name.toLowerCase().includes(query.value.toLowerCase()) || String(profile.id).includes(query.value)));
function keyValues(text) { return Object.fromEntries(text.split(/\r?\n/).map((line) => line.split('=')).filter((parts) => parts.length > 1).map(([key, ...value]) => [key.trim(), value.join('=').trim()])); }
async function submit() {
    if (!window.confirm(`Bulkwijziging uitvoeren op ${selected.value.length} profielen?`)) return;
    const success = await store.bulk({ profiles: selected.value, mutation: form.mutation, assay: form.assay, replacement_assay: form.replacement_assay || null, settings: form.mutation === 'add' ? { dillutions: keyValues(form.dillutions), replicates: form.replicates, reference: keyValues(form.reference), reference_source: form.reference_source || null, conf_trip: form.conf_trip } : null });
    if (success) selected.value = [];
}
onMounted(async () => { store.configure(props.endpoints); await Promise.all([store.loadProfiles(), store.loadEditor()]); });
</script>

<template>
    <div v-if="store.loading" class="sample-loading"><LoaderCircle class="spin" :size="20" />Gegevens laden</div>
    <div v-else class="bulk-profile-grid">
        <section class="profile-panel"><h2><ListChecks :size="16" />Onderzoeksprofielen</h2><div class="profile-panel-body"><label class="directory-search"><Search :size="16" /><input v-model="query" type="search" placeholder="Zoek profiel"></label><div class="bulk-select-links"><button type="button" @click="selected = visibleProfiles.map((profile) => profile.id)">Alles selecteren</button><button type="button" @click="selected = []">Deselecteren</button></div></div><div class="bulk-profile-list"><label v-for="profile in visibleProfiles" :key="profile.id"><input v-model="selected" type="checkbox" :value="profile.id"><span><strong>{{ profile.name }}</strong><small>{{ profile.id }} · {{ profile.scope }}</small></span></label></div></section>
        <section class="profile-panel"><h2><ListChecks :size="16" />Wijzig het volgende</h2><div class="profile-panel-body bulk-form">
            <label><input v-model="form.mutation" type="radio" value="add">Analyse toevoegen of instellingen bijwerken</label><label><input v-model="form.mutation" type="radio" value="remove">Analyse verwijderen</label><label><input v-model="form.mutation" type="radio" value="swap">Analyse vervangen, instellingen behouden</label>
            <div class="field"><label for="bulk-assay">Analyse</label><select id="bulk-assay" v-model="form.assay"><option value="">Selecteer analyse</option><option v-for="assay in store.assays" :key="assay.id" :value="assay.id">{{ assay.id }}: {{ assay.name }}</option></select></div>
            <div v-if="form.mutation === 'swap'" class="field"><label for="bulk-replacement">Vervang met</label><select id="bulk-replacement" v-model="form.replacement_assay"><option value="">Selecteer analyse</option><option v-for="assay in store.assays" :key="assay.id" :value="assay.id">{{ assay.id }}: {{ assay.name }}</option></select></div>
            <div v-if="form.mutation === 'add'" class="form-grid bulk-settings"><div class="field"><label>Verdunningen</label><textarea v-model="form.dillutions" rows="5" placeholder="-1=0.1"></textarea></div><div class="field"><label>Referentiewaarden</label><textarea v-model="form.reference" rows="5" placeholder="kve=0"></textarea></div><div class="field"><label>Referentiebron</label><select v-model="form.reference_source"><option value="">Geen bron</option><option v-for="source in store.referenceSources" :key="source.id" :value="source.id">{{ source.name }}</option></select></div><div class="field"><label>Replica's</label><input v-model.number="form.replicates" type="number" min="0"></div><div class="field"><label>Bevestig boven</label><input v-model.number="form.conf_trip" type="number" min="0"></div></div>
            <button class="button primary" type="button" :disabled="store.saving || !selected.length || !form.assay || (form.mutation === 'swap' && !form.replacement_assay)" @click="submit">Uitvoeren op {{ selected.length }} profielen</button><div v-if="store.message" class="notice success">{{ store.message }}</div><div v-if="store.error" class="notice error">{{ store.error }}</div>
        </div></section>
    </div>
</template>