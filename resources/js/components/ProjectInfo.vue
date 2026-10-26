<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';
import { Check, Circle } from '@lucide/vue';
import Avatar from 'openvue/avatar';

const props = defineProps({
    info: { type: Object, required: true },
    mode: { type: String, required: true, validator: (value) => ['sample', 'project'].includes(value) },
    projectUrl: { type: String, default: null },
    canViewProject: { type: Boolean, default: true },
    samples: { type: Array, default: () => [] },
    currentBarcode: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['select-sample']);
const portalActive = ref(null);
const portalError = ref('');
let portalRequest = null;

function displayDate(value) {
    if (!value || !Number(value)) return '-';
    return new Date(Number(value) * 1000).toLocaleDateString('nl-NL', {
        day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'Europe/Amsterdam',
    });
}

watch(() => props.info.portal_status_url, async (url) => {
    portalRequest?.abort();
    const request = new AbortController();
    portalRequest = request;
    portalActive.value = null;
    portalError.value = '';
    try {
        const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: request.signal });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Portaalstatus kon niet worden opgehaald.');
        if (typeof result.active !== 'boolean') throw new Error('Ongeldige portaalstatus ontvangen.');
        if (portalRequest === request) portalActive.value = result.active;
    } catch (error) {
        if (error.name !== 'AbortError' && portalRequest === request) portalError.value = error.message;
    }
}, { immediate: true });

onBeforeUnmount(() => portalRequest?.abort());
</script>

<template>
    <dl :class="mode === 'sample' ? 'project-info-list' : 'project-detail-list'">
        <div>
            <dt>Klant</dt>
            <dd class="flex items-center gap-1.5">
                <Circle :size="12" fill="currentColor" :stroke-width="0" :class="portalActive === true ? 'text-[#006400]' : portalActive === false || portalError ? 'text-[#ff0000]' : 'text-muted'" :aria-label="portalError || (portalActive === null ? 'Portaalstatus laden' : portalActive ? 'Actief in portaal' : 'Niet actief in portaal')" />
                <span>{{ info.project.client_name || '-' }}</span>
                <small v-if="portalError" class="text-danger" role="alert">{{ portalError }}</small>
            </dd>
        </div>
        <div><dt>Referentie klant</dt><dd>{{ info.project.project_name || '-' }}</dd></div>
        <div v-if="mode === 'sample'"><dt>Project</dt><dd><a v-if="canViewProject" class="text-link" :href="projectUrl">{{ info.project.project_name || '-' }}</a><template v-else>{{ info.project.project_name || '-' }}</template></dd></div>
        <div><dt>Orderformulier</dt><dd><a v-if="info.order_form_url" class="text-link" :href="info.order_form_url" target="_blank" rel="noopener">Downloaden</a><template v-else>Niet beschikbaar</template></dd></div>
        <div><dt>Referentie MAZ</dt><dd>{{ info.project.reference ? `${info.project.reference}.${info.project.revision}.${Number(info.project.print_version) || 1}` : '-' }}</dd></div>
        <div><dt>Project aangemeld</dt><dd>{{ displayDate(info.project.project_date) }}</dd></div>
        <div><dt>Project status</dt><dd><span class="rounded-control border border-line bg-surface-alt px-1.5 py-0.5 text-[11px] font-semibold" :class="info.project.status === 'Geblokkeerd' ? 'text-danger' : ['Afgerond', 'Geautoriseerd', 'PDF gegenereerd'].includes(info.project.status) ? 'text-success' : 'text-ink'">{{ info.project.status }}</span></dd></div>
        <div><dt>Revisie</dt><dd>{{ info.project.revision }}</dd></div>
        <div>
            <dt>Autorisatie</dt>
            <dd v-if="Number(info.project.auth_status) === 1" class="flex items-center gap-1 text-success">Geautoriseerd <Check :size="14" aria-hidden="true" /></dd>
            <dd v-else>{{ Number(info.project.auth_status) === 2 ? 'Autorisatie in behandeling' : 'Niet geautoriseerd' }}</dd>
        </div>
        <template v-if="Number(info.project.auth_status) === 1">
            <div><dt>Autorisatie door</dt><dd class="flex items-center gap-1.5"><Avatar :image="info.project.authorized_by.avatar_url" :label="info.project.authorized_by.avatar_url ? undefined : info.project.authorized_by.name.charAt(0).toUpperCase()" size="normal" shape="circle" aria-hidden="true" />{{ info.project.authorized_by.name }}</dd></div>
            <div><dt>Autorisatie datum</dt><dd>{{ displayDate(info.project.auth_on) }}</dd></div>
        </template>
        <template v-if="Number(info.project.rap_stat) === 1">
            <div><dt>PDF gegenereerd op</dt><dd>{{ displayDate(info.project.rap_on) }}</dd></div>
            <div><dt>PDF gegenereerd door</dt><dd>{{ info.project.reported_by }}</dd></div>
        </template>
        <div v-for="field in info.fields" :key="field.name"><dt>{{ field.label }}</dt><dd>{{ field.value === '' ? '-' : field.value }}</dd></div>
        <div><dt>Inzet datum</dt><dd>{{ info.inoculation_date }}</dd></div>
        <div><dt>Inzet tijd</dt><dd>{{ info.inoculation_time }}</dd></div>
        <div><dt>Monstername tot analyse:</dt><dd>{{ info.sampling_to_analysis }}</dd></div>
        <div><dt>Gereed verwacht:</dt><dd>{{ info.expected_date }}</dd></div>
        <div v-for="(field, index) in info.extra_fields" :key="index"><dt>{{ field.label }}</dt><dd>{{ field.value || '-' }}</dd></div>
        <div v-if="mode === 'sample'"><dt>Notities</dt><dd>{{ info.project.project_notes || '-' }}</dd></div>
        <div v-if="mode === 'sample' && samples.length > 1">
            <dt><label for="lookup-project-sample">Andere Monsters in project</label></dt>
            <dd>
                <select id="lookup-project-sample" class="form-control mt-1.5" :value="currentBarcode" :disabled="disabled" @change="emit('select-sample', $event.target.value)">
                    <option v-for="sample in samples" :key="sample.id" :value="sample.barcode">
                        {{ sample.barcode }} · {{ sample.description }}
                    </option>
                </select>
            </dd>
        </div>
    </dl>
</template>

<style>



</style>
