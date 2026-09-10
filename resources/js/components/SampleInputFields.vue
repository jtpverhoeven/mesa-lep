<script setup>
import { useCreateSampleStore } from '../stores/createSampleStore';

const store = useCreateSampleStore();
</script>

<template>
    <div class="field"><label for="sample-barcode">Barcode</label><input id="sample-barcode" :value="store.barcode" readonly aria-describedby="sample-barcode-help"><small id="sample-barcode-help">Voorspeld volgend nummer</small></div>
    <div class="field"><label for="sample-count">Aantal monsters</label><input id="sample-count" value="1" type="number" min="1" disabled></div>
    <div class="field wide"><label for="sample-description">Omschrijving</label><input id="sample-description" v-model="store.form.description" required></div>
    <div class="field"><label for="sample-register-as">Aanmelden als</label><select id="sample-register-as" disabled><option>Standaard</option></select></div>
    <div class="field"><label for="sample-storage">Bewaarconditie</label><select id="sample-storage" v-model="store.form.stored_in"><option value="">Niet opgegeven</option><option value="1">+3 C</option><option value="2">+7 C</option><option value="3">-18 C</option><option value="4">Kamertemperatuur</option></select></div>
    <div class="field wide"><label for="sample-method">Bemonsteringsmethode</label><select id="sample-method" v-model="store.form.sampling_method" required><option value="" disabled>Selecteer een methode</option><option v-for="method in store.samplingMethods" :key="method.id" :value="String(method.id)">{{ method.name }}</option></select></div>
    <div v-for="field in store.sampleFields" :key="field.name" class="field wide">
        <label :for="`sample-field-${field.name}`">{{ field.alias }}</label>
        <textarea v-if="field.type === 'textarea'" :id="`sample-field-${field.name}`" v-model="store.form.custom_fields[field.name]" rows="3"></textarea>
        <input v-else :id="`sample-field-${field.name}`" v-model="store.form.custom_fields[field.name]" type="text" :placeholder="field.type === 'date' ? 'dd-mm-jjjj' : ''">
    </div>
    <div class="field wide"><label for="sample-note">Monsternotities</label><textarea id="sample-note" v-model="store.form.sample_note" rows="4"></textarea></div>
</template>