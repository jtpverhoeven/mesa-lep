<script setup>
defineProps({
    category: { type: Object, default: () => ({}) },
    action: { type: String, required: true },
    cancelUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});
</script>

<template>
    <form method="POST" :action="action">
        <input type="hidden" name="_token" :value="csrf">
        <fieldset class="form-section"><legend>Basisgegevens</legend><div class="form-grid">
            <div class="field wide"><label for="name">Naam</label><input id="name" name="name" :value="category.name" maxlength="65535" required autofocus></div>
        </div></fieldset>
        <div class="form-actions"><button class="button primary" type="submit">Klantcategorie aanmaken</button><a class="button" :href="cancelUrl">Annuleren</a></div>
    </form>
</template>