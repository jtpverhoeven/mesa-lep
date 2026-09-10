<script setup>
import { ref } from 'vue';
import { ChevronDown, ChevronUp, Pencil, Trash2 } from '@lucide/vue';
import { useSampleResearchStore } from '../stores/sampleResearchStore';

defineEmits(['edit-profile-assay', 'edit-roaming']);
const store = useSampleResearchStore();
const expanded = ref(new Set());

function toggle(key) {
    const next = new Set(expanded.value);
    if (next.has(key)) next.delete(key); else next.add(key);
    expanded.value = next;
}
</script>

<template>
    <div v-if="!store.selected.length" class="selected-research-empty">Geen onderzoek geselecteerd.</div>
    <div v-else class="selected-research-list">
        <article v-for="entry in store.selected" :key="entry.key" class="selected-research-item">
            <header><button v-if="entry.type === 'profile'" class="selected-research-title" type="button" @click="toggle(entry.key)"><component :is="expanded.has(entry.key) ? ChevronUp : ChevronDown" :size="15" /><span><strong>{{ entry.profile.name }}</strong><small>Onderzoeksprofiel · {{ store.effectiveAssays(entry).length }} analyses</small></span></button><div v-else class="selected-research-title"><span><strong>{{ entry.assay.name }}</strong><small>Losse analyse</small></span></div><div class="selected-research-actions"><button v-if="entry.type === 'assay'" class="icon-button" type="button" title="Instellingen wijzigen" @click="$emit('edit-roaming', entry)"><Pencil :size="14" /></button><button class="icon-button danger" type="button" title="Verwijderen" @click="store.remove(entry.key)"><Trash2 :size="14" /></button></div></header>
            <div v-if="entry.type === 'profile' && expanded.has(entry.key)" class="selected-profile-assays"><div v-for="assay in store.effectiveAssays(entry)" :key="assay.id"><span>{{ assay.name }}</span><button class="icon-button" type="button" title="Instellingen voor dit monster wijzigen" @click="$emit('edit-profile-assay', entry, assay)"><Pencil :size="13" /></button></div></div>
        </article>
    </div>
</template>