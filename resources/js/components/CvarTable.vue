<script setup>
import { ref } from 'vue';
import { Save } from '@lucide/vue';

const props = defineProps({
    cvars: { type: Array, default: () => [] },
    updateUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});

const values = ref(Object.fromEntries(props.cvars.map((cvar) => [cvar.id, cvar.value ?? ''])));
const savingId = ref(null);
const savedId = ref(null);
const error = ref('');

async function save(cvar) {
    savingId.value = cvar.id;
    savedId.value = null;
    error.value = '';

    try {
        const response = await fetch(props.updateUrl.replace('__CVAR__', cvar.id), {
            method: 'PUT',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': props.csrf,
            },
            body: JSON.stringify({ value: values.value[cvar.id] }),
        });

        if (!response.ok) throw new Error();

        const result = await response.json();
        values.value[cvar.id] = result.value ?? '';
        savedId.value = cvar.id;
    } catch {
        error.value = 'De instelling kon niet worden opgeslagen.';
    } finally {
        savingId.value = null;
    }
}
</script>

<template>
    <div>
        <p v-if="error" class="notice error" role="alert">{{ error }}</p>
        <div class="table-scroll">
            <table class="data-table cvar-table">
                <thead>
                    <tr>
                        <th>Variabele</th>
                        <th>Instelling</th>
                        <th>Standaardwaarde</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="cvar in cvars" :key="cvar.id">
                        <td>
                            <strong>{{ cvar.cvar }}</strong>
                            <div class="muted cvar-description">{{ cvar.description }}</div>
                        </td>
                        <td>
                            <div class="cvar-value-control">
                                <label class="sr-only" :for="`cvar-${cvar.id}`">Waarde voor {{ cvar.cvar }}</label>
                                <input :id="`cvar-${cvar.id}`" v-model="values[cvar.id]" type="text" maxlength="1024">
                                <button class="button primary" type="button" :disabled="savingId === cvar.id" @click="save(cvar)">
                                    <Save :size="15" aria-hidden="true" />
                                    <span>{{ savingId === cvar.id ? 'Opslaan...' : 'Opslaan' }}</span>
                                </button>
                            </div>
                            <span v-if="savedId === cvar.id" class="cvar-saved" role="status">Opgeslagen</span>
                        </td>
                        <td>{{ cvar.default }}</td>
                    </tr>
                    <tr v-if="!cvars.length">
                        <td colspan="3">Geen geavanceerde instellingen beschikbaar.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>