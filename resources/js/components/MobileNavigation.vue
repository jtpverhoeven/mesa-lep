<script setup>
import { nextTick, onMounted, onUnmounted, ref } from 'vue';
import { Menu, X } from '@lucide/vue';

const expanded = ref(false);
const root = ref(null);
const trigger = ref(null);

function close(restoreFocus = true) {
    expanded.value = false;

    if (restoreFocus) {
        trigger.value?.focus();
    }
}

async function open() {
    expanded.value = true;

    await nextTick();
    root.value?.querySelector('.sidebar a.selected, .sidebar a')?.focus();
}

function toggle() {
    if (expanded.value) {
        close();
        return;
    }

    open();
}

function dismiss(event) {
    const path = event.composedPath();

    if (expanded.value && root.value && trigger.value && !path.includes(root.value) && !path.includes(trigger.value)) {
        close(false);
    }
}

onMounted(() => {
    document.addEventListener('click', dismiss);
});

onUnmounted(() => {
    document.removeEventListener('click', dismiss);
});
</script>

<template>
    <div ref="root" class="navigation-drawer" @keydown.esc.prevent="close">
        <Teleport to=".topbar">
            <button ref="trigger" class="mobile-nav-trigger" type="button" aria-label="Navigatiemenu" aria-controls="workspace-navigation" :aria-expanded="expanded" @click="toggle">
                <Menu v-if="!expanded" :size="19" aria-hidden="true" />
                <X v-else :size="19" aria-hidden="true" />
                <span class="sr-only">{{ expanded ? 'Navigatie sluiten' : 'Navigatie openen' }}</span>
            </button>
        </Teleport>
        <div v-if="expanded" class="mobile-nav-backdrop" aria-hidden="true" @click="close(false)"></div>
        <div id="workspace-navigation" class="navigation-panel" :class="{ 'is-open': expanded }">
            <slot />
        </div>
    </div>
</template>
