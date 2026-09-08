<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { ChevronDown, LogOut, Moon, Sun } from '@lucide/vue';

defineProps({ name: String, email: String, logoutUrl: String, csrf: String });
const expanded = ref(false);
const root = ref(null);
const trigger = ref(null);
const darkMode = ref(document.documentElement.dataset.theme === 'dark');

function toggleTheme() {
    darkMode.value = !darkMode.value;
    const theme = darkMode.value ? 'dark' : 'light';

    document.documentElement.dataset.theme = theme;
    window.localStorage.setItem('mesa-lims-theme', theme);
}

function dismiss(event) {
    if (!root.value?.contains(event.target)) expanded.value = false;
}

function close() {
    expanded.value = false;
    trigger.value?.focus();
}

onMounted(() => document.addEventListener('click', dismiss));
onUnmounted(() => document.removeEventListener('click', dismiss));
</script>

<template>
    <div ref="root" class="user-menu" @keydown.esc.prevent="close">
        <button ref="trigger" class="user-trigger" type="button" :aria-label="`Account: ${name}`" :aria-expanded="expanded" aria-controls="account-menu" @click="expanded = !expanded">
            <span class="avatar" aria-hidden="true">{{ name?.charAt(0).toUpperCase() }}</span>
            <span class="user-name">{{ name }}</span>
            <ChevronDown :size="14" aria-hidden="true" />
        </button>
        <div v-if="expanded" id="account-menu" class="user-dropdown">
            <strong>{{ name }}</strong>
            <span class="muted">{{ email }}</span>
            <button class="button theme-toggle" type="button" :aria-label="darkMode ? 'Lichte modus' : 'Donkere modus'" :aria-pressed="darkMode" @click="toggleTheme">
                <Sun v-if="darkMode" :size="15" aria-hidden="true" />
                <Moon v-else :size="15" aria-hidden="true" />
                <span>{{ darkMode ? 'Lichte modus' : 'Donkere modus' }}</span>
            </button>
            <form :action="logoutUrl" method="post">
                <input type="hidden" name="_token" :value="csrf">
                <button class="button" type="submit"><LogOut :size="15" aria-hidden="true" />Uitloggen</button>
            </form>
        </div>
    </div>
</template>