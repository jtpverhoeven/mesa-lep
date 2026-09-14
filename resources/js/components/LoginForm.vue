<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    action: { type: String, required: true },
    csrf: { type: String, required: true },
    initialLogin: { type: String, default: '' },
    remember: { type: Boolean, default: false },
    status: { type: String, default: '' },
});

const loginInput = ref(null);

onMounted(() => loginInput.value?.focus());
</script>

<template>
    <section class="login-panel">
        <div class="eyebrow">MESA LIMS</div>
        <h1>Inloggen</h1>
        <div v-if="status" class="notice success" role="status">{{ status }}</div>
        <form method="POST" :action="action">
            <input type="hidden" name="_token" :value="csrf">
            <div class="field"><label for="login">Gebruikersnaam of e-mailadres</label><input id="login" ref="loginInput" name="login" :value="initialLogin" required autocomplete="username"></div>
            <div class="field"><label for="password">Wachtwoord</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
            <div class="check-grid"><label><input name="remember" type="checkbox" value="1" :checked="remember"> Ingelogd blijven</label></div>
            <button type="submit" class="button primary">Inloggen</button>
        </form>
    </section>
</template>