<script setup>
defineProps({ data: { type: Object, required: true } });

function date(value) {
    if (!value || !Number(value)) return '-';
    return new Date(Number(value) * 1000).toLocaleString('nl-NL');
}

function display(value) {
    return value !== null && typeof value === 'object' ? JSON.stringify(value) : (value ?? '-');
}
</script>

<template>
    <dl class="lookup-details">
        <dt>Barcode</dt><dd><strong>{{ data.sample.barcode }}</strong></dd>
        <dt>Volgnummer</dt><dd>{{ data.project_follow_number }}</dd>
        <dt>Omschrijving</dt><dd>{{ data.sample.description }}</dd>
        <dt>Klant</dt><dd>{{ data.client?.name ?? data.sample.client }}</dd>
        <dt>Klantomschrijving</dt><dd>{{ data.sample.client_description }}</dd>
        <dt>Aangemeld</dt><dd>{{ date(data.sample.date_registered) }}</dd>
        <dt>Door</dt><dd>{{ data.registered_by ?? '-' }}</dd>
        <dt>Monstername</dt><dd>{{ data.sampling_method ?? '-' }}</dd>
        <dt>Type</dt><dd>{{ data.sample.sample_type }}</dd>
        <dt>Ingezet</dt><dd>{{ date(data.sample.sample_innoculated) }}</dd>
        <dt>Opgeslagen</dt><dd>{{ data.sample.stored_in || '-' }}</dd>
        <dt>Afweegstation</dt><dd>{{ data.sample.diluted_at || '-' }}</dd>
        <dt>Bron</dt><dd>{{ ({ 0: 'LIMS', 1: 'CSV import', 2: 'LIMS', 3: 'Portal' })[data.sample.source] ?? data.sample.source }}</dd>
        <template v-for="field in data.sample_fields" :key="field.name"><dt>{{ field.label }}</dt><dd>{{ display(field.value) }}</dd></template>
        <template v-for="field in data.sample_extra" :key="field.name"><dt>{{ field.label }}</dt><dd>{{ display(field.value) }}</dd></template>
    </dl>
</template>
