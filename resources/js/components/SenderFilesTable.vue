<script setup>
import { computed, ref } from 'vue';
import { History } from '@lucide/vue';
import Button from 'openvue/button';
import { fillUrl, getJson } from '../senderApi';
import AppDialog from './AppDialog.vue';

const props = defineProps({
    files: { type: Array, required: true },
    historyUrl: { type: String, required: true },
    modelValue: { type: Array, required: true },
    label: { type: String, required: true },
});
const emit = defineEmits(['update:modelValue']);
const historyVisible = ref(false);
const historyLoading = ref(false);
const historyError = ref('');
const historyFile = ref(null);
const history = ref([]);
const hashes = computed(() => props.files.map((file) => file.hash_name));

function toggleAll() {
    const allSelected = hashes.value.length > 0 && hashes.value.every((hash) => props.modelValue.includes(hash));
    emit('update:modelValue', allSelected ? [] : [...hashes.value]);
}

function update(hash, checked) {
    const selected = new Set(props.modelValue);
    if (checked) selected.add(hash); else selected.delete(hash);
    emit('update:modelValue', hashes.value.filter((item) => selected.has(item)));
}

async function showHistory(file) {
    historyFile.value = file;
    history.value = [];
    historyError.value = '';
    historyVisible.value = true;
    historyLoading.value = true;
    try {
        history.value = (await getJson(fillUrl(props.historyUrl, { __FILE__: file.id }))).history;
    } catch {
        historyError.value = 'De verzendgeschiedenis kon niet worden geladen.';
    } finally {
        historyLoading.value = false;
    }
}
</script>

<template>
    <div class="table-scroll">
        <table class="data-table">
            <caption class="sr-only">{{ label }}</caption>
            <thead>
                <tr>
                    <th>Barcode</th><th>Bestandsnaam</th><th>Status</th><th>Upload datum</th>
                    <th>Meesturen <button type="button" class="text-link" @click="toggleAll">[wisselen]</button></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="file in files" :key="file.id">
                    <td>{{ file.barcode ?? '-' }}</td>
                    <td class="wrap-anywhere">{{ file.original_name }}</td>
                    <td>
                        {{ file.sent_label }}
                        <Button v-if="file.sent_count > 0" type="button" severity="secondary" variant="text" class="result-table-action" :aria-label="`Verzendgeschiedenis van ${file.original_name}`" @click="showHistory(file)"><History :size="14" /></Button>
                    </td>
                    <td>{{ file.created_at }}</td>
                    <td><input type="checkbox" :checked="modelValue.includes(file.hash_name)" :aria-label="`${file.original_name} meesturen`" @change="update(file.hash_name, $event.target.checked)"></td>
                </tr>
                <tr v-if="!files.length"><td colspan="5" class="empty-state">Geen zichtbare bestanden gevonden</td></tr>
            </tbody>
        </table>
    </div>

    <AppDialog v-model:visible="historyVisible" title="Verzend geschiedenis" width="820px">
        <p v-if="historyFile" class="mb-3 text-muted wrap-anywhere">{{ historyFile.original_name }}</p>
        <p v-if="historyLoading" class="report-feedback" role="status">Bezig met laden . . .</p>
        <p v-else-if="historyError" class="report-feedback danger" role="alert">{{ historyError }}</p>
        <p v-else-if="!history.length" class="report-feedback" role="status">Bestand nog niet verstuurd</p>
        <div v-else class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Datum</th><th>Verzonden via</th><th>Verzonden door</th><th>Contactgroepen</th><th>Extra ontvangers</th></tr></thead>
                <tbody>
                    <tr v-for="entry in history" :key="entry.id">
                        <td>{{ entry.sent_on }}</td>
                        <td><a v-if="entry.url" class="text-link" :href="entry.url">{{ entry.method }}</a><span v-else>{{ entry.method }}</span></td>
                        <td>{{ entry.sent_by ?? '-' }}</td>
                        <td>{{ entry.groups.join(', ') || '-' }}</td>
                        <td class="wrap-anywhere">{{ entry.extra || '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppDialog>
</template>
