<script setup>
import { computed } from 'vue';
import { LockKeyhole } from '@lucide/vue';
import Avatar from 'openvue/avatar';

const props = defineProps({
    project: { type: Object, default: null },
});

const blocked = computed(() => Number(props.project?.locked) === 1);
const blockedBy = computed(() => props.project?.authorization_blocked_by);
const blockedByName = computed(() => blockedBy.value?.name || 'een andere gebruiker');
const message = computed(() => props.project?.lock_message || 'Geen reden opgegeven.');
</script>

<template>
    <div v-if="blocked" class="status-notice authorization-blocked-notice" role="status" aria-live="polite">
        <Avatar :image="blockedBy?.avatar_url || undefined" :label="blockedBy?.avatar_url ? undefined : blockedByName.charAt(0).toUpperCase()" shape="circle" aria-hidden="true" />
        <div class="status-notice-text">
            <strong><LockKeyhole :size="15" aria-hidden="true" />Autorisatie geblokkeerd door {{ blockedByName }}.</strong>
            <span>{{ message }}</span>
        </div>
    </div>
</template>
