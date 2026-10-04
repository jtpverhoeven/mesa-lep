<script setup>
import InputText from 'openvue/inputtext';

defineProps({ rows: Array, legionella: Boolean, profiles: Array, samplingMethods: Array, previewing: Boolean });
</script>

<template>
    <div class="table-scroll bulk-sample-table-wrap">
        <table class="bulk-sample-table">
            <thead><tr><th scope="col">Volgnr.</th><template v-if="legionella"><th scope="col">Type water / test</th><th scope="col">Monstername</th><th scope="col">Monsternummer (indicatief)</th></template><template v-else><th scope="col">Monsternummer (indicatief)</th><th scope="col">Ruimte</th><th scope="col">Omschrijving plaats van monsterneming</th></template></tr></thead>
            <tbody>
                <tr v-for="(row, index) in rows" :key="index">
                    <td class="bulk-follow"><input v-model="row.follow" type="text" :disabled="legionella" :aria-label="`Volgnummer monster ${index + 1}`"></td>
                    <template v-if="legionella">
                        <td><select v-model="row.profile_id" required :aria-label="`Type water of test monster ${index + 1}`"><option v-for="profile in profiles" :key="profile.id" :value="profile.id">{{ profile.name }}</option></select></td>
                        <td><select v-model="row.sampling_method" required :aria-label="`Monstername monster ${index + 1}`"><option v-for="method in samplingMethods" :key="method.id" :value="method.id">{{ method.name }}</option></select></td>
                        <td class="bulk-barcode"><InputText :model-value="previewing ? '...' : row.barcode" disabled unstyled :aria-busy="previewing" :aria-label="`Monsternummer monster ${index + 1}`" /></td>
                    </template>
                    <template v-else>
                        <td class="bulk-barcode"><InputText :model-value="previewing ? '...' : row.barcode" disabled unstyled :aria-busy="previewing" :aria-label="`Monsternummer monster ${index + 1}`" /></td>
                        <td><InputText v-model="row.location" unstyled :aria-label="`Ruimte monster ${index + 1}`" /></td>
                        <td><InputText v-model="row.description" unstyled :aria-label="`Plaats van monsterneming monster ${index + 1}`" /></td>
                    </template>
                </tr>
                <tr v-if="!rows.length"><td colspan="4" class="empty-state">Geen monsters</td></tr>
            </tbody>
        </table>
    </div>
</template>