<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { Download, ExternalLink, LoaderCircle, MailCheck, Trash2, UploadCloud } from '@lucide/vue';
import ToggleSwitch from 'openvue/toggleswitch';
import AppConfirmDialog from './AppConfirmDialog.vue';

const props = defineProps({
    files: { type: Array, default: () => [] },
    sampleId: { type: Number, required: true },
    endpoints: { type: Object, required: true },
    canManage: { type: Boolean, default: false },
    locked: { type: Boolean, default: false },
    projectReadOnly: { type: Boolean, default: false },
});
const emit = defineEmits(['update:files']);
const fileInput = ref(null);
const uploading = ref(false);
const uploadingCompleted = ref(0);
const uploadingTotal = ref(0);
const dragging = ref(false);
const mutatingId = ref(null);
const error = ref('');
const pendingAction = ref(null);
let activeRequest = null;
const confirmationVisible = computed({
    get: () => pendingAction.value !== null,
    set: (visible) => {
        if (! visible && mutatingId.value === null) pendingAction.value = null;
    },
});
const disabled = computed(() => ! props.canManage || props.locked || uploading.value || mutatingId.value !== null);

function endpoint(template, fileId = null) {
    return template
        .replace('__SAMPLE__', props.sampleId)
        .replace('__FILE__', fileId ?? '__FILE__');
}

async function responseData(response, fallback) {
    const payload = await response.json();

    if (! response.ok) {
        throw new Error(Object.values(payload.errors ?? {}).flat().join(' ') || payload.message || fallback);
    }

    emit('update:files', payload.data);
}

async function refreshFiles(signal) {
    const response = await fetch(endpoint(props.endpoints.store), {
        headers: { Accept: 'application/json' },
        signal,
    });

    if (! response.ok) throw new Error('De bestandslijst kon niet opnieuw worden geladen.');

    emit('update:files', (await response.json()).data);
}

async function uploadFiles(files) {
    const queuedFiles = Array.from(files ?? []);

    if (! queuedFiles.length || disabled.value) return;

    uploading.value = true;
    uploadingCompleted.value = 0;
    uploadingTotal.value = queuedFiles.length;
    error.value = '';
    const request = new AbortController();
    activeRequest = request;
    const failures = [];

    try {
        for (const file of queuedFiles) {
            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await fetch(endpoint(props.endpoints.store), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: formData,
                    signal: request.signal,
                });
                await responseData(response, 'Het bestand kon niet worden toegevoegd.');
            } catch (exception) {
                if (exception.name === 'AbortError') return;
                failures.push(`${file.name}: ${exception.message}`);
            } finally {
                uploadingCompleted.value++;
            }
        }

        if (failures.length) {
            error.value = failures.join(' ');

            try {
                await refreshFiles(request.signal);
            } catch (refreshException) {
                if (refreshException.name === 'AbortError') return;
                error.value = `${error.value} ${refreshException.message}`;
            }
        }

        if (fileInput.value) fileInput.value.value = '';
    } finally {
        if (activeRequest === request) {
            activeRequest = null;
            uploading.value = false;
            dragging.value = false;
        }
    }
}

function selectFiles(event) {
    void uploadFiles(event.target.files);
}

function dropFiles(event) {
    dragging.value = false;
    void uploadFiles(event.dataTransfer?.files);
}

function requestVisibility(file, visible) {
    if (disabled.value) return;
    pendingAction.value = { type: 'visibility', file, visible };
}

function displayedVisibility(file) {
    if (pendingAction.value?.type === 'visibility' && pendingAction.value.file.id === file.id) {
        return pendingAction.value.visible;
    }

    return Boolean(file.visible_for_client);
}

function requestDelete(file) {
    if (disabled.value) return;
    pendingAction.value = { type: 'delete', file };
}

async function confirmAction() {
    const action = pendingAction.value;

    if (! action || disabled.value) return;

    mutatingId.value = action.file.id;
    error.value = '';
    const request = new AbortController();
    activeRequest = request;

    try {
        const visibility = action.type === 'visibility';
        const response = await fetch(endpoint(
            visibility ? props.endpoints.visibility : props.endpoints.destroy,
            action.file.id,
        ), {
            method: visibility ? 'PATCH' : 'DELETE',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: visibility ? JSON.stringify({ visible_for_client: action.visible }) : undefined,
            signal: request.signal,
        });
        await responseData(
            response,
            visibility ? 'De zichtbaarheid kon niet worden gewijzigd.' : 'Het bestand kon niet worden verwijderd.',
        );
        pendingAction.value = null;
    } catch (exception) {
        if (exception.name === 'AbortError') return;
        error.value = exception.message;
    } finally {
        if (activeRequest === request) {
            activeRequest = null;
            mutatingId.value = null;
        }
    }
}

onBeforeUnmount(() => activeRequest?.abort());
</script>

<template>
    <div class="grid gap-3">
        <p v-if="locked" class="rounded-control border border-line bg-surface-alt p-2 text-xs text-muted">
            Dit monster is in gebruik door een andere gebruiker. Bestanden kunnen alleen worden bekeken.
        </p>
        <p v-if="error" class="error-text" role="alert">{{ error }}</p>

        <div v-if="canManage" class="grid gap-3">
            <label
                :for="`sample-file-${sampleId}`"
                class="grid min-h-36 place-items-center rounded-control border-2 border-dashed p-5 text-center transition-colors"
                :class="[
                    dragging ? 'border-accent bg-accent-faint' : 'border-control-border bg-surface hover:bg-surface-alt',
                    disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
                ]"
                :aria-disabled="disabled"
                @dragenter.prevent="dragging = ! disabled"
                @dragover.prevent="dragging = ! disabled"
                @dragleave.prevent="dragging = false"
                @drop.prevent="dropFiles"
            >
                <input :id="`sample-file-${sampleId}`" ref="fileInput" class="sr-only" type="file" multiple :disabled="disabled" @change="selectFiles">
                <span class="pointer-events-none grid justify-items-center gap-2">
                    <LoaderCircle v-if="uploading" class="spin text-accent" :size="30" />
                    <UploadCloud v-else class="text-accent" :size="30" />
                    <strong>{{ uploading ? `${uploadingCompleted} van ${uploadingTotal} bestanden verwerkt` : 'Sleep bestanden hierheen' }}</strong>
                    <span class="text-xs text-muted">{{ uploading ? 'De wachtrij wordt op volgorde verwerkt.' : 'Of klik om een of meerdere bestanden te kiezen.' }}</span>
                </span>
            </label>
        </div>

        <div v-if="files.length" class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Bestandsnaam</th>
                        <th>Klant zichtbaar</th>
                        <th>Acties</th>
                        <th class="sr-only">Verzendstatus</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="file in files" :key="file.id">
                        <td class="wrap-anywhere">
                            <a class="text-link inline-flex items-center gap-1" :href="file.download_url">
                                <Download :size="13" />{{ file.original_name }}
                            </a>
                        </td>
                        <td>
                            <ToggleSwitch
                                :model-value="displayedVisibility(file)"
                                :input-id="`sample-file-visible-${file.id}`"
                                :disabled="disabled"
                                :aria-label="`${file.original_name} zichtbaar voor klant`"
                                @update:model-value="requestVisibility(file, $event)"
                            />
                        </td>
                        <td>
                            <div class="flex items-center gap-1">
                                <a class="icon-button" :href="file.preview_url" target="_blank" title="Bestand tonen" aria-label="Bestand tonen"><ExternalLink :size="14" /></a>
                                <button v-if="canManage" class="icon-button danger" type="button" title="Bestand verwijderen" aria-label="Bestand verwijderen" :disabled="disabled" @click="requestDelete(file)"><Trash2 :size="14" /></button>
                            </div>
                        </td>
                        <td>
                            <MailCheck v-if="file.sent_to_client" :size="15" :title="file.sent_count ? `Verstuurd, ${file.sent_count} keer` : 'Eerder verstuurd'" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="empty-state">Geen bestanden toegevoegd.</p>

        <AppConfirmDialog
            v-model:visible="confirmationVisible"
            :title="pendingAction?.type === 'delete' ? 'Bestand verwijderen' : 'Zichtbaarheid wijzigen'"
            :danger="pendingAction?.type === 'delete' || projectReadOnly"
            :confirm-label="pendingAction?.type === 'delete' ? 'Verwijderen' : 'Wijzigen'"
            :loading="mutatingId !== null"
            :disabled="! pendingAction"
            :error="error"
            @confirm="confirmAction"
        >
            <p class="m-0">
                <template v-if="pendingAction?.type === 'delete'">
                    Bestand <strong>{{ pendingAction.file.original_name }}</strong> verwijderen? Deze verwijdering wordt ook naar het klantportaal gesynchroniseerd.
                </template>
                <template v-else>
                    Bestand <strong>{{ pendingAction?.file.original_name }}</strong> {{ pendingAction?.visible ? 'zichtbaar' : 'niet zichtbaar' }} maken voor de klant? De wijziging wordt direct naar het klantportaal gesynchroniseerd.
                </template>
            </p>
            <p v-if="projectReadOnly" class="mt-2 text-xs text-danger">Dit bestand hoort bij een vergrendeld of geautoriseerd project.</p>
            <template #confirm-icon><Trash2 v-if="pendingAction?.type === 'delete'" :size="15" /><ExternalLink v-else :size="15" /></template>
        </AppConfirmDialog>
    </div>
</template>
