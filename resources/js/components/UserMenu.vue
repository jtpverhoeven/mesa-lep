<script setup>
import { nextTick, onMounted, onUnmounted, ref } from 'vue';
import Avatar from 'openvue/avatar';
import { Check, ChevronRight, LogOut, Moon, Settings, Sun, UserRound } from '@lucide/vue';

const props = defineProps({ name: String, email: String, avatarUrl: String, settingsUrl: String, logoutUrl: String, csrf: String });
const expanded = ref(false);
const themesExpanded = ref(false);
const root = ref(null);
const trigger = ref(null);
const name = ref(props.name);
const avatar = ref(props.avatarUrl);
const darkMode = ref(document.documentElement.dataset.theme === 'dark');

function setTheme(theme) {
    darkMode.value = theme === 'dark';
    document.documentElement.dataset.theme = theme;
    window.localStorage.setItem('mesa-lims-theme', theme);
}

function dismiss(event) {
    if (!root.value?.contains(event.target)) {
        expanded.value = false;
        themesExpanded.value = false;
    }
}

function close() {
    expanded.value = false;
    themesExpanded.value = false;
    trigger.value?.focus();
}

async function openWithKeyboard() {
    expanded.value = true;
    await nextTick();
    root.value?.querySelector('.user-dropdown a')?.focus();
}

function updateAccount(event) {
    if ('name' in event.detail) name.value = event.detail.name;
    if ('avatar' in event.detail) avatar.value = event.detail.avatar;
}

onMounted(() => {
    document.addEventListener('click', dismiss);
    window.addEventListener('mesa:account-updated', updateAccount);
});
onUnmounted(() => {
    document.removeEventListener('click', dismiss);
    window.removeEventListener('mesa:account-updated', updateAccount);
});
</script>

<template>
    <div ref="root" class="user-menu" @keydown.esc.prevent="close" @focusout="event => { if (!root?.contains(event.relatedTarget)) expanded = false; }">
        <button ref="trigger" class="user-trigger" type="button" :aria-label="`Account: ${name}`" :aria-expanded="expanded" aria-controls="account-menu" @click="expanded = !expanded" @keydown.down.prevent="openWithKeyboard">
            <Avatar :image="avatar" :label="avatar ? undefined : name?.charAt(0).toUpperCase()" shape="circle" aria-hidden="true" />
        </button>
        <div v-if="expanded" id="account-menu" class="user-dropdown">
            <div class="user-menu-identity">
                <Avatar :image="avatar" :label="avatar ? undefined : name?.charAt(0).toUpperCase()" size="xlarge" shape="circle" aria-hidden="true" />
                <div class="min-w-0"><strong>{{ name }}</strong><span class="text-muted break-all">{{ email }}</span></div>
            </div>
            <a class="user-menu-item" :href="`${settingsUrl}#personal`"><UserRound :size="17" aria-hidden="true" />Profiel</a>
            <a class="user-menu-item" :href="settingsUrl"><Settings :size="17" aria-hidden="true" />Accountinstellingen</a>
            <button class="user-menu-item" type="button" :aria-expanded="themesExpanded" aria-controls="account-theme-options" @click="themesExpanded = !themesExpanded">
                <Moon v-if="darkMode" :size="17" aria-hidden="true" /><Sun v-else :size="17" aria-hidden="true" />
                Thema <ChevronRight class="ml-auto" :size="15" aria-hidden="true" />
            </button>
            <div v-if="themesExpanded" id="account-theme-options" class="user-theme-options" role="group" aria-label="Thema">
                <button v-for="theme in ['light', 'dark']" :key="theme" type="button" class="user-menu-item" :aria-pressed="darkMode === (theme === 'dark')" @click="setTheme(theme)">
                    {{ theme === 'light' ? 'Licht' : 'Donker' }}
                    <Check v-if="darkMode === (theme === 'dark')" class="ml-auto" :size="15" aria-hidden="true" />
                </button>
            </div>
            <form :action="logoutUrl" method="post">
                <input type="hidden" name="_token" :value="csrf">
                <button class="user-menu-item" type="submit"><LogOut :size="17" aria-hidden="true" />Uitloggen</button>
            </form>
        </div>
    </div>
</template>
