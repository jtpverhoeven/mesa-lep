<script setup>
import { computed, ref } from 'vue';
import { Plus, Search, Trash2 } from '@lucide/vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    storeUrl: { type: String, required: true },
    destroyUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    canManage: { type: Boolean, default: false },
});

const categories = ref(props.categories);
const name = ref('');
const search = ref('');
const saving = ref(false);
const deletingId = ref(null);
const error = ref('');

const filteredCategories = computed(() => {
    const query = search.value.trim().toLocaleLowerCase();
    return query ? categories.value.filter((category) => category.name.toLocaleLowerCase().includes(query)) : categories.value;
});

async function addCategory() {
    const trimmedName = name.value.trim();
    if (!trimmedName) return;

    saving.value = true;
    error.value = '';
    try {
        const response = await fetch(props.storeUrl, {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': props.csrf },
            body: JSON.stringify({ name: trimmedName }),
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message);

        categories.value = [...categories.value, result.category].sort((first, second) => first.name.localeCompare(second.name));
        name.value = '';
    } catch (exception) {
        error.value = exception.message || 'De klantcategorie kon niet worden aangemaakt.';
    } finally {
        saving.value = false;
    }
}

async function deleteCategory(category) {
    if (!window.confirm(`Klantcategorie "${category.name}" verwijderen? De klantkoppelingen worden ook verwijderd.`)) return;

    deletingId.value = category.id;
    error.value = '';
    try {
        const response = await fetch(props.destroyUrl.replace('__CATEGORY__', category.id), {
            method: 'DELETE',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': props.csrf },
        });
        if (!response.ok) throw new Error();
        categories.value = categories.value.filter((currentCategory) => currentCategory.id !== category.id);
    } catch {
        error.value = 'De klantcategorie kon niet worden verwijderd.';
    } finally {
        deletingId.value = null;
    }
}
</script>

<template>
    <div class="client-category-manager">
        <p v-if="error" class="notice error" role="alert">{{ error }}</p>
        <form v-if="canManage" class="category-create" @submit.prevent="addCategory">
            <label class="sr-only" for="new-category-name">Nieuwe klantcategorie</label>
            <input id="new-category-name" v-model="name" maxlength="65535" placeholder="Nieuwe klantcategorie">
            <button class="button primary" type="submit" :disabled="saving || !name.trim()"><Plus :size="16" aria-hidden="true" /> {{ saving ? 'Toevoegen...' : 'Toevoegen' }}</button>
        </form>
        <label class="directory-search category-search">
            <Search :size="16" aria-hidden="true" />
            <span class="sr-only">Klantcategorieen zoeken</span>
            <input v-model="search" type="search" placeholder="Zoek klantcategorie">
        </label>
        <div class="table-scroll"><table class="data-table"><thead><tr><th>Naam</th><th>Actieve klanten</th><th v-if="canManage">Actie</th></tr></thead><tbody>
            <tr v-for="category in filteredCategories" :key="category.id"><td><strong>{{ category.name }}</strong></td><td>{{ category.active_clients_count }}</td><td v-if="canManage"><button class="icon-button danger" type="button" title="Klantcategorie verwijderen" :aria-label="`Klantcategorie ${category.name} verwijderen`" :disabled="deletingId === category.id" @click="deleteCategory(category)"><Trash2 :size="16" aria-hidden="true" /></button></td></tr>
            <tr v-if="!filteredCategories.length"><td :colspan="canManage ? 3 : 2" class="empty-state">Geen klantcategorieen gevonden.</td></tr>
        </tbody></table></div>
    </div>
</template>