<script setup>
import { computed, ref } from 'vue';
import { Search } from '@lucide/vue';

const props = defineProps({
    client: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    action: { type: String, required: true },
    method: { type: String, default: 'POST' },
    submitLabel: { type: String, required: true },
    cancelUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});

const query = ref('');
const selectedCategoryIds = ref((props.client.categories ?? []).map((category) => String(
    typeof category === 'object' ? category.id : category,
)));

const filteredCategories = computed(() => {
    const normalizedQuery = query.value.trim().toLocaleLowerCase();
    return normalizedQuery
        ? props.categories.filter((category) => category.name.toLocaleLowerCase().includes(normalizedQuery))
        : props.categories;
});

function toggleCategory(id) {
    const stringId = String(id);
    selectedCategoryIds.value = selectedCategoryIds.value.includes(stringId)
        ? selectedCategoryIds.value.filter((selectedId) => selectedId !== stringId)
        : [...selectedCategoryIds.value, stringId];
}
</script>

<template>
    <form :method="method === 'GET' ? 'GET' : 'POST'" :action="action">
        <input type="hidden" name="_token" :value="csrf">
        <input v-if="method !== 'POST' && method !== 'GET'" type="hidden" name="_method" :value="method">
        <fieldset class="form-section"><legend>Basisgegevens</legend><div class="form-grid">
            <div class="field wide"><label for="name">Bedrijfsnaam</label><input id="name" name="name" :value="client.name" maxlength="256" required autofocus></div>
            <div class="field"><label for="street_name">Straatnaam</label><input id="street_name" name="street_name" :value="client.street_name" maxlength="128"></div><div class="field"><label for="street_number">Huisnummer</label><input id="street_number" name="street_number" :value="client.street_number" maxlength="6"></div>
            <div class="field"><label for="postal_code">Postcode</label><input id="postal_code" name="postal_code" :value="client.postal_code" maxlength="12"></div><div class="field"><label for="place">Plaats</label><input id="place" name="place" :value="client.place" maxlength="128"></div>
            <div class="field"><label for="country">Land</label><input id="country" name="country" :value="client.country" maxlength="128"></div><div class="field"><label for="telephone">Telefoonnummer</label><input id="telephone" name="telephone" :value="client.telephone" maxlength="32"></div>
            <div class="field"><label for="cellphone">GSM nummer</label><input id="cellphone" name="cellphone" :value="client.cellphone" maxlength="32"></div><div class="field"><label for="email">E-mail</label><input id="email" name="email" type="email" :value="client.email" maxlength="512"></div>
        </div></fieldset>
        <fieldset class="form-section"><legend>Contactpersoon</legend><div class="form-grid">
            <div class="field"><label for="title">Aanspreektitel</label><input id="title" name="title" :value="client.title" maxlength="12"></div><div class="field"><label for="fname">Voornaam</label><input id="fname" name="fname" :value="client.fname" maxlength="128"></div>
            <div class="field"><label for="mname">Tussenvoegsel</label><input id="mname" name="mname" :value="client.mname" maxlength="128"></div><div class="field"><label for="lname">Achternaam</label><input id="lname" name="lname" :value="client.lname" maxlength="128"></div>
        </div></fieldset>
        <fieldset class="form-section"><legend>Administratie</legend><div class="form-grid">
            <div class="field"><label for="nvwa_number">NVWA nummer</label><input id="nvwa_number" name="nvwa_number" :value="client.nvwa_number"></div><div class="field"><label for="debit_number">Debiteur nummer</label><input id="debit_number" name="debit_number" :value="client.debit_number"></div>
            <div class="field wide"><label for="notes">Wensen en opmerkingen</label><textarea id="notes" name="notes" :value="client.notes"></textarea></div>
        </div></fieldset>
        <fieldset class="form-section"><legend>Klantcategorieen</legend>
            <label class="directory-search category-search"><Search :size="16" aria-hidden="true" /><span class="sr-only">Klantcategorieen zoeken</span><input v-model="query" type="search" placeholder="Zoek klantcategorie"></label>
            <div class="category-options"><label v-for="category in filteredCategories" :key="category.id" class="category-option"><input type="checkbox" :checked="selectedCategoryIds.includes(String(category.id))" @change="toggleCategory(category.id)"><span>{{ category.name }}</span></label><p v-if="!filteredCategories.length" class="muted">Geen klantcategorieen gevonden.</p></div>
            <input v-for="id in selectedCategoryIds" :key="id" type="hidden" name="categories[]" :value="id">
        </fieldset>
        <div class="form-actions"><button class="button primary" type="submit">{{ submitLabel }}</button><a class="button" :href="cancelUrl">Annuleren</a></div>
    </form>
</template>