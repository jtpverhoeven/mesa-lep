<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { Barcode, Camera, LoaderCircle, Search, Upload } from '@lucide/vue';
import Button from 'openvue/button';
import InputText from 'openvue/inputtext';
import AppConfirmDialog from './AppConfirmDialog.vue';
import ProjectInfo from './ProjectInfo.vue';
import SampleInfoList from './SampleInfoList.vue';

const props = defineProps({
    lookupUrl: { type: String, required: true },
    uploadUrl: { type: String, required: true },
    maxEdge: { type: Number, required: true },
    canViewProject: { type: Boolean, default: false },
});
const barcode = ref('');
const barcodeInput = ref(null);
const fileInput = ref(null);
const selected = ref(null);
const photo = ref(null);
const previewUrl = ref('');
const previewAvailable = ref(true);
const loading = ref(false);
const uploading = ref(false);
const confirming = ref(false);
const error = ref('');
const status = ref('');
let lookupRequest = null;
let uploadRequest = null;

watch(barcode, (value) => {
    if (selected.value && value.trim() !== selected.value.sample.barcode) {
        selected.value = null;
        clearPhoto();
    }
});

function clearPhoto() {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = '';
    photo.value = null;
    if (fileInput.value) fileInput.value.value = '';
}

function selectPhoto(event) {
    const file = event.target.files?.[0] ?? null;
    clearPhoto();
    photo.value = file;
    previewAvailable.value = true;
    error.value = '';
    status.value = '';
    if (photo.value) previewUrl.value = URL.createObjectURL(photo.value);
}

async function lookup() {
    if (uploading.value || !barcode.value.trim()) return;
    lookupRequest?.abort();
    const request = new AbortController();
    lookupRequest = request;
    selected.value = null;
    clearPhoto();
    confirming.value = false;
    loading.value = true;
    error.value = '';
    status.value = '';

    try {
        const response = await fetch(`${props.lookupUrl}?${new URLSearchParams({ barcode: barcode.value.trim() })}`, {
            headers: { Accept: 'application/json' },
            signal: request.signal,
        });
        if (response.redirected || [401, 419].includes(response.status)) throw new Error('Je sessie is verlopen. Vernieuw deze pagina.');
        if (!response.ok) throw new Error(response.status === 404 ? 'Geen monster gevonden met deze barcode.' : 'Monster kon niet worden geladen.');
        const { data } = await response.json();
        if (lookupRequest === request) selected.value = data;
    } catch (exception) {
        if (exception.name !== 'AbortError' && lookupRequest === request) error.value = exception.message;
    } finally {
        if (lookupRequest === request) {
            lookupRequest = null;
            loading.value = false;
            barcodeInput.value?.$el?.focus();
            barcodeInput.value?.$el?.select();
        }
    }
}

function selectSample(scanned) {
    barcode.value = scanned;
    void lookup();
}

function requestUpload() {
    if (uploading.value) return;
    if (!selected.value) {
        error.value = 'Geen monster gescand.';
        return;
    }
    if (!photo.value) {
        error.value = 'Geen foto geselecteerd.';
        return;
    }

    error.value = '';
    if (Number(selected.value.project_info?.project.auth_status) === 1) confirming.value = true;
    else void upload();
}

async function jpegPhoto(file) {
    if (file.size <= 20 * 1024 * 1024
        && !/\.(heic|heif|avif)$/i.test(file.name)
        && !['image/heic', 'image/heif', 'image/avif'].includes(file.type)) {
        return file;
    }

    const url = URL.createObjectURL(file);
    const image = new Image();

    try {
        image.src = url;
        try {
            await image.decode();
        } catch {
            throw new Error('Dit fotoformaat kan op dit apparaat niet worden geopend. Kies een JPEG-foto.');
        }

        const scale = Math.min(1, props.maxEdge / Math.max(image.naturalWidth, image.naturalHeight));
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
        canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
        const context = canvas.getContext('2d');
        if (!context) throw new Error('De foto kan op dit apparaat niet worden omgezet naar JPEG.');
        context.fillStyle = '#fff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.9));
        if (!blob || blob.type !== 'image/jpeg') throw new Error('De foto kan op dit apparaat niet worden omgezet naar JPEG.');

        return new File([blob], `${file.name.replace(/\.[^.]+$/, '')}.jpg`, { type: 'image/jpeg' });
    } finally {
        URL.revokeObjectURL(url);
    }
}

async function upload() {
    if (!selected.value || !photo.value || uploading.value) return;
    confirming.value = false;
    uploading.value = true;
    error.value = '';
    const request = new AbortController();
    uploadRequest = request;

    try {
        const formData = new FormData();
        formData.append('file', await jpegPhoto(photo.value));
        formData.append('photo_mode', '1');
        formData.append('instant_visible', '1');
        const response = await fetch(props.uploadUrl.replace('__SAMPLE__', selected.value.sample.id), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: formData,
            signal: request.signal,
        });
        if (response.redirected || [401, 419].includes(response.status)) throw new Error('Je sessie is verlopen. Vernieuw deze pagina.');
        const result = await response.json();
        if (!response.ok) throw new Error(Object.values(result.errors ?? {}).flat().join(' ') || result.message || 'De foto kon niet worden opgeslagen.');

        selected.value = null;
        clearPhoto();
        barcode.value = '';
        status.value = 'Foto opgeslagen.';
        await nextTick();
        barcodeInput.value?.$el?.focus();
    } catch (exception) {
        if (exception.name !== 'AbortError') error.value = exception.message;
    } finally {
        if (uploadRequest === request) {
            uploadRequest = null;
            uploading.value = false;
        }
    }
}

onBeforeUnmount(() => {
    lookupRequest?.abort();
    uploadRequest?.abort();
    clearPhoto();
});
</script>

<template>
    <div class="sample-create-grid">
        <div class="sample-create-column grid min-w-0 content-start gap-3">
            <section class="sample-panel">
                <h2><Barcode :size="15" />Monster opzoeken</h2>
                <form class="sample-panel-body flex gap-2" @submit.prevent="lookup">
                    <label class="sr-only" for="photo-barcode">Barcode</label>
                    <InputText id="photo-barcode" ref="barcodeInput" v-model="barcode" autofocus class="min-w-0 flex-1" placeholder="Barcode" autocomplete="off" :disabled="uploading" />
                    <Button type="submit" :disabled="loading || uploading || !barcode.trim()" aria-label="Monster zoeken" title="Monster zoeken">
                        <Search :size="16" />
                    </Button>
                </form>
            </section>
            <section class="sample-panel">
                <h2><Camera :size="15" />Selecteer foto</h2>
                <div class="sample-panel-body">
                    <label for="sample-photo" class="button inline-flex items-center gap-1.5" :class="selected && !uploading && !loading ? 'cursor-pointer' : 'cursor-not-allowed opacity-50'" :aria-disabled="!selected || uploading || loading">
                        <Camera :size="15" />Selecteer foto
                    </label>
                    <input id="sample-photo" ref="fileInput" class="sr-only" type="file" accept="image/*" capture="environment" :disabled="!selected || uploading || loading" @change="selectPhoto">
                </div>
            </section>
            <section v-if="photo" class="sample-panel">
                <h2><Camera :size="15" />Foto</h2>
                <div class="sample-panel-body grid gap-2">
                    <img v-if="previewAvailable" :src="previewUrl" alt="Voorbeeld van geselecteerde foto" class="max-h-72 max-w-full object-contain" @error="previewAvailable = false">
                    <p v-else class="error-text">Voorbeeld niet beschikbaar.</p>
                    <Button type="button" :disabled="uploading || loading || !selected" @click="requestUpload">
                        <LoaderCircle v-if="uploading" class="spin" :size="15" />
                        <Upload v-else :size="15" />
                        Opslaan
                    </Button>
                </div>
            </section>
            <p v-if="error" class="error-text" role="alert">{{ error }}</p>
            <p v-if="status" class="text-xs text-success" role="status">{{ status }}</p>
        </div>

        <section class="sample-panel sample-create-column">
            <h2><Search :size="15" />Gescand monster</h2>
            <div v-if="selected" class="sample-panel-body"><SampleInfoList :data="selected" /></div>
        </section>

        <section class="sample-panel sample-create-column">
            <h2><Search :size="15" />Project</h2>
            <div v-if="selected?.project_info" class="sample-panel-body">
                <ProjectInfo :info="selected.project_info" mode="sample" :project-url="selected.project_url" :can-view-project="canViewProject" :samples="selected.project_samples" :current-barcode="selected.sample.barcode" :disabled="uploading || loading" @select-sample="selectSample" />
            </div>
        </section>

        <AppConfirmDialog
            v-model:visible="confirming"
            title="Foto toevoegen?"
            confirm-label="Opslaan"
            danger
            @confirm="upload"
        >
            <p class="m-0">Dit monster is deel van een geautoriseerd project. De foto wordt direct naar het portaal geupload.</p>
        </AppConfirmDialog>
    </div>
</template>
