<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    portalAssay: { type: Object, default: null },
    assays: { type: Array, default: () => [] },
    selectedAssayIds: { type: Array, default: () => [] },
    showAssociations: Boolean,
    formAction: { type: String, required: true },
    associationToggleAction: { type: String, default: '' },
    indexUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    addToAllClients: Boolean,
});

const form = ref({
    commonName: props.portalAssay?.common_name ?? '',
    commonNameEn: props.portalAssay?.common_name_en ?? '',
    selectable: Boolean(props.portalAssay?.selectable ?? true),
    alertable: Boolean(props.portalAssay?.alertable ?? true),
    borderReaction: Boolean(props.portalAssay?.border_reaction ?? false),
    addToAllClients: props.addToAllClients,
});
const selectedAssayIds = ref(new Set(props.selectedAssayIds.map(Number)));
const pendingAssayIds = ref(new Set());
const associationError = ref('');
const showInactiveAssays = ref(false);
const hideAssaysAssignedElsewhere = ref(true);
const visibleAssays = computed(() => props.assays.filter((assay) => (
    (showInactiveAssays.value || assay.active)
    && (!hideAssaysAssignedElsewhere.value || !assay.assigned_elsewhere)
)));

function selected(assayId) {
    return selectedAssayIds.value.has(Number(assayId));
}

function setAssaySelected(assayId, checked) {
    const next = new Set(selectedAssayIds.value);
    checked ? next.add(Number(assayId)) : next.delete(Number(assayId));
    selectedAssayIds.value = next;
}

async function toggleAssay(assayId, checked) {
    const id = Number(assayId);
    const previous = selected(id);

    setAssaySelected(id, checked);
    associationError.value = '';
    pendingAssayIds.value = new Set(pendingAssayIds.value).add(id);

    try {
        const response = await fetch(props.associationToggleAction, {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': props.csrf,
            },
            body: JSON.stringify({ assay_id: id, attached: checked }),
        });
        const payload = await response.json().catch(() => null);

        if (!response.ok) {
            associationError.value = payload?.message ?? 'De analysekoppeling kon niet worden bijgewerkt.';
            setAssaySelected(id, previous);

            return;
        }

        setAssaySelected(id, Boolean(payload.attached));
    } catch {
        associationError.value = 'De analysekoppeling kon niet worden bijgewerkt. Controleer de netwerkverbinding en probeer opnieuw.';
        setAssaySelected(id, previous);
    } finally {
        const pending = new Set(pendingAssayIds.value);
        pending.delete(id);
        pendingAssayIds.value = pending;
    }
}
</script>

<template>
    <div class="portal-assay-editor">
        <form class="editor-panel" :action="formAction" method="post">
            <input type="hidden" name="_token" :value="csrf">
            <input v-if="portalAssay" type="hidden" name="_method" value="PUT">
            <fieldset><legend>Portaalanalyse</legend><div class="form-grid"><label class="field field-wide"><span>Naam</span><input v-model="form.commonName" name="common_name" required maxlength="1000" autofocus></label><label class="field field-wide"><span>Naam Engels</span><input v-model="form.commonNameEn" name="common_name_en" maxlength="1000"></label></div></fieldset>
            <fieldset><legend>Instellingen</legend><div class="check-grid"><label><input type="hidden" name="selectable" value="0"><input v-model="form.selectable" type="checkbox" name="selectable" value="1"> Selecteerbaar door klant</label><label><input type="hidden" name="alertable" value="0"><input v-model="form.alertable" type="checkbox" name="alertable" value="1"> Klant kan alarm instellen</label><label><input type="hidden" name="border_reaction" value="0"><input v-model="form.borderReaction" type="checkbox" name="border_reaction" value="1"> Grensreactie</label><label v-if="addToAllClients"><input type="hidden" name="add_to_all_clients" value="0"><input v-model="form.addToAllClients" type="checkbox" name="add_to_all_clients" value="1"> Toevoegen aan alle actieve klanten</label></div></fieldset>
            <div class="form-actions"><a class="button secondary" :href="indexUrl">Annuleren</a><button class="button primary" type="submit">{{ portalAssay ? 'Opslaan' : 'Portaalanalyse aanmaken' }}</button></div>
        </form>

        <section v-if="showAssociations" class="editor-panel associations-panel">
            <div class="association-heading"><div><h2>Gekoppelde analyses</h2><p>Een analyse kan slechts aan een portaalanalyse gekoppeld zijn.</p></div><div class="association-filters"><label><input v-model="showInactiveAssays" type="checkbox"> Toon ook inactieve analyses</label><label><input v-model="hideAssaysAssignedElsewhere" type="checkbox"> Verberg analyses gekoppeld aan een andere portaalanalyse</label></div></div>
            <p v-if="associationError" class="error-text" role="alert">{{ associationError }}</p>
            <div class="assay-grid"><label v-for="assay in visibleAssays" :key="assay.id" class="assay-option" :class="{ selected: selected(assay.id), inactive: !assay.active, unavailable: assay.assigned_elsewhere }"><input type="checkbox" :checked="selected(assay.id)" :disabled="assay.assigned_elsewhere || pendingAssayIds.has(assay.id)" @change="toggleAssay(assay.id, $event.target.checked)"><span class="assay-name">{{ assay.name }}</span><span class="assay-id" title="Analyse-ID">{{ assay.id }}</span><small v-if="assay.assigned_elsewhere">Gekoppeld aan andere portaalanalyse</small><small v-else-if="pendingAssayIds.has(assay.id)">Bijwerken...</small><small v-else-if="!assay.active">Inactief</small></label></div>
            <p v-if="!visibleAssays.length" class="empty-state">Geen analyses gevonden voor deze filters.</p>
        </section>
    </div>
</template>

<style scoped>
.portal-assay-editor { display:grid; gap:20px; max-width:1100px; }
.editor-panel { display:grid; gap:18px; }
fieldset { margin:0; border:1px solid var(--line); padding:16px; min-width:0; }
legend { padding:0 5px; font-weight:700; }
.form-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:14px; }
.field { display:grid; gap:6px; font-size:13px; }
.field-wide { min-width:0; }
.field input { min-width:0; }
.check-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:10px; }
.check-grid label, .assay-option { overflow-wrap:anywhere; }
.form-actions { display:flex; justify-content:flex-end; flex-wrap:wrap; gap:8px; }
.association-heading { display:flex; justify-content:space-between; align-items:start; gap:16px; flex-wrap:wrap; }
.association-heading h2 { margin:0; font-size:16px; }
.association-heading p { margin:5px 0 0; color:var(--muted); font-size:13px; }
.association-filters { display:grid; gap:7px; font-size:12px; }
.association-filters label { display:flex; align-items:center; gap:6px; max-width:310px; }
.assay-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); border-top:1px solid var(--line); border-left:1px solid var(--line); }
.assay-option { display:grid; grid-template-columns:auto minmax(0, 1fr) auto; gap:5px 9px; align-items:start; padding:10px; border-right:1px solid var(--line); border-bottom:1px solid var(--line); font-size:13px; }
.assay-name { min-width:0; }
.assay-id { min-width:30px; padding:2px 5px; border:1px solid var(--line); border-radius:2px; background:var(--surface-alt); color:var(--muted); font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-size:11px; line-height:1; text-align:center; }
.assay-option small { grid-column:2 / -1; color:var(--muted); }
.assay-option.selected { background:#e7f4ec; box-shadow:inset 4px 0 #147a45; }
.assay-option.selected .assay-id { border-color:#147a45; background:#d1eadb; color:#075a2e; font-weight:700; }
.assay-option.inactive { background:var(--surface-alt); }
.assay-option.selected.inactive { background:#dcece2; }
.assay-option.unavailable { color:var(--muted); }
@media (max-width:760px) { .form-grid, .check-grid { grid-template-columns:minmax(0, 1fr); } .assay-grid { grid-template-columns:minmax(0, 1fr); } }
</style>