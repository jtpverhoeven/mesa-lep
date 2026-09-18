<script setup>
import { computed, ref } from 'vue';
import Button from 'openvue/button';
import InputText from 'openvue/inputtext';
import Textarea from 'openvue/textarea';
import { PlugZap, Save } from '@lucide/vue';

const props = defineProps({
    settings: { type: Object, required: true },
    portalUrl: { type: String, required: true },
    updateUrl: { type: String, required: true },
    testUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});

const acceptType = ref(props.settings.acceptType ?? 'application/json');
const bearer = ref(props.settings.bearer ?? '');
const lastSync = ref(props.settings.lastSync ?? null);
const busyAction = ref(null);
const notice = ref(null);
const validationErrors = ref({});

const formattedLastSync = computed(() => {
    if (!lastSync.value) return 'Nog niet gesynchroniseerd';

    return new Intl.DateTimeFormat('nl-NL', {
        dateStyle: 'medium',
        timeStyle: 'medium',
    }).format(new Date(lastSync.value));
});

async function updateSettings(showConfirmation = true) {
    validationErrors.value = {};

    const response = await fetch(props.updateUrl, {
        method: 'PUT',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': props.csrf,
        },
        body: JSON.stringify({
            acceptType: acceptType.value,
            bearer: bearer.value,
        }),
    });

    const result = await response.json();

    if (!response.ok) {
        validationErrors.value = result.errors ?? {};
        throw new Error(result.message ?? 'De verbindingsinstellingen konden niet worden opgeslagen.');
    }

    if (showConfirmation) {
        notice.value = { type: 'success', message: result.message };
    }
}

async function save() {
    busyAction.value = 'save';
    notice.value = null;

    try {
        await updateSettings();
    } catch (error) {
        notice.value = { type: 'error', message: error.message };
    } finally {
        busyAction.value = null;
    }
}

async function testConnection() {
    busyAction.value = 'test';
    notice.value = null;

    try {
        await updateSettings(false);

        const response = await fetch(props.testUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': props.csrf,
            },
        });
        const result = await response.json();

        if (!response.ok) throw new Error(result.message ?? 'De verbindingstest is mislukt.');

        lastSync.value = new Date().toISOString();
        notice.value = { type: 'success', message: result.message };
    } catch (error) {
        notice.value = { type: 'error', message: error.message };
    } finally {
        busyAction.value = null;
    }
}
</script>

<template>
    <form class="portal-connection-form" @submit.prevent="save">
        <div v-if="notice" :class="['notice', notice.type]" role="status">{{ notice.message }}</div>

        <fieldset class="form-section">
            <legend>API-verbinding</legend>
            <div class="form-grid">
                <div class="field wide">
                    <label for="portal-url">Client portal API URL</label>
                    <InputText id="portal-url" :model-value="portalUrl" readonly />
                </div>
                <div class="field wide">
                    <label for="portal-accept-type">Accept type</label>
                    <InputText id="portal-accept-type" v-model="acceptType" maxlength="128" required autocomplete="off" />
                    <span v-if="validationErrors.acceptType" class="field-error">{{ validationErrors.acceptType[0] }}</span>
                </div>
                <div class="field wide">
                    <label for="portal-bearer">Authorization bearer</label>
                    <Textarea id="portal-bearer" v-model="bearer" rows="8" required autocomplete="off" />
                    <span v-if="validationErrors.bearer" class="field-error">{{ validationErrors.bearer[0] }}</span>
                </div>
                <div class="field wide">
                    <label>Laatste synchronisatie</label>
                    <output>{{ formattedLastSync }}</output>
                </div>
            </div>
        </fieldset>

        <div class="form-actions">
            <Button type="button" class="button" :unstyled="true" :disabled="busyAction !== null" @click="testConnection">
                <PlugZap :size="15" aria-hidden="true" />
                <span>{{ busyAction === 'test' ? 'Testen...' : 'Test verbinding' }}</span>
            </Button>
            <Button type="submit" class="button primary" :unstyled="true" :disabled="busyAction !== null">
                <Save :size="15" aria-hidden="true" />
                <span>{{ busyAction === 'save' ? 'Opslaan...' : 'Opslaan' }}</span>
            </Button>
        </div>
    </form>
</template>
