<script setup>
import { computed, ref } from 'vue';
import { LoaderCircle, Plus, Save, X } from '@lucide/vue';
import { useConfirmationStore } from '../stores/confirmationStore';
import ConfirmationRacetrack from './ConfirmationRacetrack.vue';

const store = useConfirmationStore();
const note = ref('');
const applicableScopes = computed(() => store.data?.scopes?.filter((scope) => scope.applicable) ?? []);

function setNote(value) {
    note.value = value;
}

async function saveNote() {
    await store.saveNote(note.value);
}

function scopeLabel(scope) {
    if (scope.df === 'global') return 'Globaal';

    const value = Number(scope.df);
    const exponent = value > 0 ? -Math.log10(value) : Number.NaN;
    const dilution = Number.isInteger(exponent) ? (exponent === 0 ? '0' : `-${exponent}`) : String(scope.df);
    const replicate = Number(scope.rep) === 0 ? '' : ` · replica ${Number(scope.rep) + 1}`;

    return `${dilution}${replicate}`;
}
</script>

<template>
    <div v-if="store.dialogOpen" class="confirmation-modal-backdrop" role="presentation" @click.self="store.close()">
        <section class="confirmation-modal" role="dialog" aria-modal="true" aria-labelledby="confirmation-title">
            <header>
                <div><h2 id="confirmation-title">Bevestiging</h2><small v-if="store.data">{{ store.data.status }}</small></div>
                <button class="icon-button" type="button" title="Sluiten" aria-label="Bevestiging sluiten" @click="store.close()"><X :size="16" /></button>
            </header>
            <div class="confirmation-modal-body">
                <p v-if="store.error" class="error-text" role="alert">{{ store.error }}</p>
                <div v-if="applicableScopes.length > 1" class="confirmation-scope-tabs" role="tablist" aria-label="Bevestigingsscope">
                    <button v-for="scope in applicableScopes" :key="`${scope.df}:${scope.rep}`" type="button" :aria-selected="store.selectedScopeKey === `${scope.df}:${scope.rep}`" @click="store.load(store.analysisId, scope.df, scope.rep)">{{ scopeLabel(scope) }}<small>{{ scope.ready ? 'Gereed' : 'Wacht' }}</small></button>
                </div>
                <div class="confirmation-summary"><strong>{{ store.selectedEvaluation?.summary?.confirmed ?? 0 }}/{{ store.selectedEvaluation?.summary?.tested ?? 0 }}</strong><span>ratio {{ store.selectedEvaluation?.summary?.ratio ?? '-' }}</span><span>{{ store.selectedScope?.ready ? 'Gereed' : 'Nog niet gereed' }}</span><span v-if="store.pendingMutationCount" role="status">Opslaan...</span></div>
                <ConfirmationRacetrack />
                <div class="confirmation-support-list">
                    <label v-for="field in store.data?.support_fields ?? []" :key="field.media_id"><input type="checkbox" :checked="field.active" :disabled="store.readOnly" @change="store.toggleSupport(field.media_id, $event.target.checked)">{{ field.name }}</label>
                </div>
                <label class="confirmation-note"><span>Notitie</span><textarea :value="store.data?.note ?? ''" :disabled="store.readOnly" rows="3" @input="setNote($event.target.value)" @change="saveNote"></textarea></label>
            </div>
            <footer>
                <button class="button" type="button" :disabled="store.readOnly || !store.selectedScope" @click="store.addContender"><Plus :size="15" />Kolonie toevoegen</button>
                <button v-if="store.selectedEvaluation?.contenders?.length" class="button" type="button" :disabled="store.readOnly" @click="store.removeContender(store.selectedEvaluation.contenders.at(-1).index)">Laatste verwijderen</button>
                <button class="button primary" type="button" :disabled="store.loading" @click="store.close"><Save :size="15" />Sluiten</button>
            </footer>
        </section>
    </div>
</template>

<style scoped>
.confirmation-modal-backdrop { position:fixed; z-index:70; inset:0; display:grid; place-items:center; padding:18px; background:rgba(20,35,45,.48); }
.confirmation-modal { width:min(980px,100%); max-height:calc(100vh - 36px); overflow:auto; border:1px solid #8e9ba2; background:var(--surface); box-shadow:0 18px 45px rgba(20,35,45,.3); }
.confirmation-modal > header { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:11px 14px; border-bottom:1px solid var(--line); background:var(--surface-alt); }
.confirmation-modal h2,.confirmation-modal small { margin:0; }
.confirmation-modal h2 { font-size:16px; }
.confirmation-modal header small { display:block; margin-top:3px; color:var(--muted); }
.confirmation-modal-body { display:grid; gap:14px; padding:14px; }
.confirmation-scope-tabs { display:flex; overflow:auto; border-bottom:1px solid var(--line); }
.confirmation-scope-tabs button { display:grid; gap:2px; min-width:110px; padding:8px 10px; border:0; border-bottom:2px solid transparent; background:var(--surface); color:var(--muted); font:inherit; text-align:left; cursor:pointer; }
.confirmation-scope-tabs button[aria-selected=true] { border-bottom-color:var(--accent); background:var(--accent-faint); color:var(--accent); }
.confirmation-scope-tabs small { color:inherit; font-size:10px; }
.confirmation-summary { display:flex; flex-wrap:wrap; gap:14px; color:var(--muted); font-size:12px; }
.confirmation-summary strong { color:var(--ink); }
.confirmation-support-list { display:flex; flex-wrap:wrap; gap:10px 16px; padding-top:10px; border-top:1px solid var(--line); }
.confirmation-support-list label { display:flex; align-items:center; gap:6px; font-size:11px; }
.confirmation-note { display:grid; gap:5px; font-size:11px; font-weight:600; }
.confirmation-note textarea { width:100%; resize:vertical; border:1px solid #9eabb2; background:var(--surface); color:var(--ink); padding:7px 9px; font:inherit; }
.confirmation-modal > footer { display:flex; justify-content:flex-end; flex-wrap:wrap; gap:8px; padding:10px 14px; border-top:1px solid var(--line); background:var(--surface-alt); }
</style>