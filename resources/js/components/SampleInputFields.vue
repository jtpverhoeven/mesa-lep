<script setup>
import { ref } from 'vue';
import { useCreateSampleStore } from '../stores/createSampleStore';

const store = useCreateSampleStore();
const description = ref(null);

defineExpose({ focusDescription: () => description.value?.focus() });
</script>

<template>
    <div class="field"><label for="sample-barcode">Barcode</label><input id="sample-barcode" :value="store.barcode" readonly aria-describedby="sample-barcode-help"><small id="sample-barcode-help">Voorspeld volgend nummer</small></div>
    <div class="field"><label for="sample-count">Aantal monsters</label><input id="sample-count" value="1" type="number" min="1" disabled></div>
    <div class="field wide"><label for="sample-description">Omschrijving</label><input id="sample-description" ref="description" v-model="store.form.description"></div>
    <div class="field"><label for="sample-register-as">Aanmelden als</label><select id="sample-register-as" v-model="store.form.register_as"><option value="standard">Normaal monster</option><option value="tht">THT monster</option><option value="buffer">Aanmelden in buffer</option></select></div>
    <template v-if="store.form.register_as !== 'standard'">
        <div class="field"><label for="sample-sampling-date">Bemonsterdatum</label><input id="sample-sampling-date" v-model="store.form.sampling_date" type="date"></div>
        <div class="field"><label for="sample-receive-date">Ontvangstdatum</label><input id="sample-receive-date" v-model="store.form.receive_date" type="date"></div>
        <div class="field"><label for="sample-receive-time">Ontvangsttijd</label><input id="sample-receive-time" v-model="store.form.receive_time" type="time"></div>
    </template>
    <template v-if="store.form.register_as === 'tht'">
        <div class="field"><label for="sample-tht-date">Inzetdatum THT</label><input id="sample-tht-date" v-model="store.form.tht_date" type="date"></div>
        <div class="field"><label for="sample-tht-storage">Bewaartemperatuur</label><select id="sample-tht-storage" v-model="store.form.tht_storage"><option value="4">+3°C</option><option value="0">+4°C</option><option value="1">+7°C</option><option value="2">-18°C</option><option value="3">Kamertemperatuur</option></select></div>
    </template>
    <div class="field wide"><label for="sample-method">Bemonsteringsmethode</label><select id="sample-method" v-model="store.form.sampling_method"><option value="">Geen methode</option><option v-for="method in store.samplingMethods" :key="method.id" :value="String(method.id)">{{ method.name }}</option></select></div>
    <div v-for="field in store.sampleFields" :key="field.name" class="field wide">
        <label :for="`sample-field-${field.name}`">{{ field.alias }}</label>
        <textarea v-if="field.type === 'textarea'" :id="`sample-field-${field.name}`" v-model="store.form.custom_fields[field.name]" rows="3"></textarea>
        <input v-else :id="`sample-field-${field.name}`" v-model="store.form.custom_fields[field.name]" type="text" :placeholder="field.type === 'date' ? 'dd-mm-jjjj' : ''">
    </div>
    <div class="field wide"><label for="sample-note">Monsternotities</label><textarea id="sample-note" v-model="store.form.sample_note" rows="4"></textarea></div>
</template>