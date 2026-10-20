<script setup>
import { onBeforeUnmount, ref } from 'vue';
import Button from 'openvue/button';
import AppDialog from './AppDialog.vue';

const props = defineProps({ file: { type: File, required: true }, busy: Boolean });
const emit = defineEmits(['close', 'save']);
const container = ref(null);
const ready = ref(false);
const encoding = ref(false);
const error = ref('');
const sourceUrl = URL.createObjectURL(props.file);
let cropper;
let disposed = false;

async function initialize() {
    try {
        const { default: Cropper } = await import('cropperjs');
        const image = new Image();
        image.src = sourceUrl;
        image.alt = 'Afbeelding om bij te snijden';
        await image.decode();
        if (disposed) return;
        if (image.naturalWidth > 4096 || image.naturalHeight > 4096) {
            throw new Error('Gebruik een afbeelding van maximaal 4096 × 4096 pixels.');
        }
        cropper = new Cropper(image, { container: container.value });
        await cropper.getCropperImage().$ready();
        if (disposed) return;
        cropper.getCropperImage().$center('contain');
        const selection = cropper.getCropperSelection();
        selection.aspectRatio = 1;
        selection.keyboard = true;
        const side = Math.min(container.value.clientWidth, 320) * 0.7;
        selection.$change(0, 0, side, side, 1).$center();
        ready.value = true;
    } catch (exception) {
        error.value = exception.message || 'De afbeelding kon niet worden geopend.';
    }
}

async function save() {
    encoding.value = true;
    error.value = '';
    try {
        const canvas = await cropper.getCropperSelection().$toCanvas({ width: 256, height: 256 });
        const blob = await new Promise((resolve, reject) => canvas.toBlob(
            value => value ? resolve(value) : reject(new Error('De uitsnede kon niet worden gemaakt.')),
            'image/png',
        ));
        if (!disposed) emit('save', new File([blob], 'avatar.png', { type: 'image/png' }));
    } catch (exception) {
        error.value = exception.message;
    } finally {
        encoding.value = false;
    }
}

onBeforeUnmount(() => {
    disposed = true;
    cropper?.destroy();
    URL.revokeObjectURL(sourceUrl);
});
</script>

<template>
    <AppDialog :visible="true" title="Profielfoto bijsnijden" width="600px" :closable="!busy && !encoding" :dismissable-mask="false" :close-on-escape="!busy && !encoding" @show="initialize" @update:visible="emit('close')">
        <p>Sleep de afbeelding of het vierkante kader. Gebruik de knoppen om in te zoomen, te draaien of te verplaatsen.</p>
        <p v-if="error" class="text-danger" role="alert">{{ error }}</p>
        <div ref="container" class="avatar-cropper" aria-label="Uitsnede van profielfoto" />
        <div class="flex flex-wrap gap-2">
            <Button v-for="direction in [{ label: 'Links', x: -10, y: 0 }, { label: 'Rechts', x: 10, y: 0 }, { label: 'Omhoog', x: 0, y: -10 }, { label: 'Omlaag', x: 0, y: 10 }]" :key="direction.label" severity="secondary" :disabled="!ready || busy || encoding" @click="cropper.getCropperImage().$move(direction.x, direction.y)">{{ direction.label }}</Button>
            <Button severity="secondary" :disabled="!ready || busy || encoding" @click="cropper.getCropperImage().$zoom(0.1)">Inzoomen</Button>
            <Button severity="secondary" :disabled="!ready || busy || encoding" @click="cropper.getCropperImage().$zoom(-0.1)">Uitzoomen</Button>
            <Button severity="secondary" :disabled="!ready || busy || encoding" @click="cropper.getCropperImage().$rotate('90deg')">Draaien</Button>
        </div>
        <slot />
        <template #footer>
            <Button severity="secondary" :disabled="busy || encoding" @click="emit('close')">Annuleren</Button>
            <Button :disabled="!ready || busy || encoding" :loading="busy || encoding" @click="save">Uitsnede opslaan</Button>
        </template>
    </AppDialog>
</template>
