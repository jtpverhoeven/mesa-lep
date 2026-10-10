<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { Check, CircleDot, Download, Droplets, FlaskConical, FolderOpen, LoaderCircle, Printer, RefreshCw, Tags } from '@lucide/vue';
import Column from 'openvue/column';
import DataTable from 'openvue/datatable';
import AppDialog from './AppDialog.vue';

const props = defineProps({
    status: { type: String, required: true },
    title: { type: String, required: true },
    endpoint: { type: String, required: true },
});
const projects = ref([]);
const total = ref(0);
const nextPage = ref(1);
const hasMore = ref(true);
const sentinel = ref(null);
const loading = ref(false);
const error = ref('');
const today = new Date();
const labelDate = ref(`${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`);
const placeholder = ref(null);
const placeholderVisible = ref(false);
let activeRequest = null;
let observer = null;

const columnsByStatus = {
    received: [
        { field: 'project_date', header: 'Aangemeld op' },
        { field: 'all_samples_have_analysis', header: 'Analyses gekoppeld' },
        { field: 'print_history', header: 'Geprint door' },
    ],
    running: [{ field: 'progress', header: 'Voortgang' }, { field: 'predicted_end', header: 'Gereed verwacht' }],
    completed: [{ field: 'completion_dates', header: 'Ontvangen / Gereed op' }],
    authorized: [{ field: 'auth_on', header: 'Geautoriseerd op' }],
    reported: [{ field: 'rap_on', header: 'PDF gegenereerd op' }, { field: 'reported_by', header: 'PDF gegenereerd door' }],
    blocked: [{ field: 'progress', header: 'Voortgang' }, { field: 'completion_dates', header: 'Gereed verwacht' }],
};
const columns = computed(() => [
    { field: 'status', header: 'Status' },
    { field: 'project', header: 'Project' },
    { field: 'client_reference', header: 'Klant referentie' },
    { field: 'samples_count', header: 'Aantal monsters in project' },
    { field: 'special_type', header: 'Type monster(s)' },
    ...(columnsByStatus[props.status] ?? []),
]);
const sampleTypes = {
    0: { label: 'Standaard', icon: FlaskConical },
    1: { label: 'Legionella', icon: Droplets },
    2: { label: 'RODAC', icon: CircleDot },
};
const placeholderTitles = {
    labels: 'Etiketten afdrukken',
    report: 'Projectrapport afdrukken',
    export: 'Projectgegevens exporteren',
};
async function loadProjects(reset = false) {
    if (loading.value || (!reset && !hasMore.value)) return;
    if (reset) {
        projects.value = [];
        total.value = 0;
        nextPage.value = 1;
        hasMore.value = true;
    }

    const request = new AbortController();
    activeRequest = request;
    loading.value = true;
    error.value = '';

    try {
        const url = new URL(props.endpoint, window.location.origin);
        url.searchParams.set('page', String(nextPage.value));
        const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: request.signal });
        const payload = await response.json().catch(() => ({}));
        if (activeRequest !== request) return;
        if (!response.ok) throw new Error(payload.message || 'Projecten konden niet worden geladen.');

        const loadedIds = new Set(projects.value.map((project) => project.id));
        projects.value.push(...payload.data.filter((project) => !loadedIds.has(project.id)));
        total.value = payload.total;
        nextPage.value = payload.current_page + 1;
        hasMore.value = payload.current_page < payload.last_page && payload.data.length > 0;
    } catch (requestError) {
        if (requestError.name === 'AbortError' || activeRequest !== request) return;
        error.value = requestError.message;
    } finally {
        if (activeRequest === request) {
            loading.value = false;
            await nextTick();
            if (activeRequest === request && hasMore.value && !error.value && sentinel.value) {
                observer?.unobserve(sentinel.value);
                observer?.observe(sentinel.value);
            }
        }
    }
}

function displayDate(value) {
    if (value === null || value === undefined || value === '') return '-';
    if (!/^-?\d+$/.test(String(value))) return String(value);
    if (Number(value) <= 0) return '-';
    return new Date(Number(value) * 1000).toLocaleDateString('nl-NL');
}

function statusTone(status) {
    if (status.includes('Geblokkeerd')) return 'blocked';
    if (status === 'Afgerond' || status === 'Geautoriseerd') return 'ready';
    if (status === 'Lopend') return 'running';
    return 'neutral';
}

function showPlaceholder(action, project) {
    placeholder.value = { action, project, labelDate: labelDate.value };
    placeholderVisible.value = true;
}

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting) && !error.value) loadProjects();
    }, { rootMargin: '260px' });
    observer.observe(sentinel.value);
    loadProjects();
});
onBeforeUnmount(() => {
    observer?.disconnect();
    activeRequest?.abort();
    activeRequest = null;
});
</script>

<template>
    <section class="sample-panel project-overview" :aria-busy="loading">
        <h2>
            <FolderOpen :size="16" />Projecten: {{ title }}
            <LoaderCircle v-if="loading" class="spin" :size="15" />
            <span class="overview-count">{{ total }} projecten</span>
            <button class="icon-button" type="button" title="Vernieuwen" aria-label="Projecten vernieuwen" :disabled="loading" @click="loadProjects(true)"><RefreshCw :size="16" /></button>
        </h2>
        <p v-if="error" class="sample-panel-body error-text overview-load-error" role="alert">{{ error }}<button class="icon-button" type="button" title="Opnieuw proberen" aria-label="Projecten opnieuw laden" :disabled="loading" @click="loadProjects()"><RefreshCw :size="16" /></button></p>
        <div v-if="status === 'received'" class="sample-panel-body overview-label-date">
            <label for="overview-label-date">Inzetdag voor etiketten</label>
            <input id="overview-label-date" v-model="labelDate" type="date">
        </div>
        <div class="table-scroll">
            <DataTable
                :value="projects"
                data-key="id"
                table-class="data-table project-overview-table"
                :unstyled="true"
            >
                <Column v-for="column in columns" :key="column.field" :field="column.field" :header="column.header">
                    <template #body="{ data }">
                        <span v-if="column.field === 'status'" class="overview-status" :class="statusTone(data.status)" :title="data.lock_message || undefined">{{ data.status }}</span>
                        <div v-else-if="column.field === 'project'" class="overview-project">
                            <a :href="data.url"><strong>{{ data.reference || data.id }}</strong></a>
                            <span>{{ data.client_name }}</span>
                            <small>Aangemaakt op: {{ displayDate(data.project_date) }}</small>
                        </div>
                        <span v-else-if="column.field === 'special_type'" class="overview-sample-type">
                            <component :is="sampleTypes[data.special_type]?.icon || FlaskConical" :size="16" aria-hidden="true" />
                            {{ sampleTypes[data.special_type]?.label || 'Onbekend' }}
                        </span>
                        <div v-else-if="column.field === 'progress'" class="overview-progress">
                            <progress :value="data.progress" max="100" :aria-label="`${data.progress}% gereed`"></progress>
                            <small>{{ data.progress }}%</small>
                        </div>
                        <template v-else-if="column.field === 'completion_dates'">{{ displayDate(data.received_date) }} / {{ displayDate(data.became_ready_on) }}</template>
                        <span v-else-if="column.field === 'all_samples_have_analysis'" class="overview-assigned" :aria-label="data.all_samples_have_analysis ? 'Alle monsters hebben analyses' : 'Niet alle monsters hebben analyses'"><Check v-if="data.all_samples_have_analysis" :size="20" aria-hidden="true" /><template v-else>-</template></span>
                        <template v-else-if="column.field === 'print_history'">{{ data.last_print_name || '-' }}<template v-if="data.print_times !== null"> ({{ data.print_times }})</template></template>
                        <span v-else-if="column.field === 'predicted_end'" class="overview-deadline" :class="data.deadline_tone" :title="data.deadline_tone === 'danger' ? 'Meer dan drie werkdagen verstreken' : data.deadline_tone === 'warning' ? 'Verwachte gereeddatum verstreken' : undefined">{{ Number(data.predicted_end) === -1 || !data.predicted_end ? 'Nog niet bekend' : displayDate(data.predicted_end) }}</span>
                        <template v-else-if="['project_date', 'auth_on', 'rap_on'].includes(column.field)">{{ displayDate(data[column.field]) }}</template>
                        <template v-else>{{ data[column.field] || (column.field === 'samples_count' ? 0 : '-') }}</template>
                    </template>
                </Column>
                <Column header="Acties">
                    <template #body="{ data }">
                        <div class="overview-actions">
                            <a class="icon-button" :href="data.url" title="Project openen" aria-label="Project openen"><FolderOpen :size="16" /></a>
                            <button v-if="status === 'received' && data.all_samples_have_analysis" class="icon-button" type="button" title="Etiketten afdrukken (nog niet beschikbaar)" aria-label="Etiketten afdrukken" :disabled="!labelDate" @click="showPlaceholder('labels', data)"><Tags :size="16" /></button>
                            <button v-else-if="status !== 'received'" class="icon-button" type="button" title="Projectrapport afdrukken (nog niet beschikbaar)" aria-label="Projectrapport afdrukken" @click="showPlaceholder('report', data)"><Printer :size="16" /></button>
                            <button v-if="['authorized', 'reported'].includes(status)" class="icon-button" type="button" title="Projectgegevens exporteren (nog niet beschikbaar)" aria-label="Projectgegevens exporteren" @click="showPlaceholder('export', data)"><Download :size="16" /></button>
                        </div>
                    </template>
                </Column>
                <template #empty><p class="empty-state" role="status">{{ loading ? 'Projecten laden...' : 'Geen projecten in deze status.' }}</p></template>
            </DataTable>
        </div>
        <div ref="sentinel" class="overview-load-sentinel" aria-hidden="true"></div>
        <p v-if="loading && projects.length" class="sample-panel-body overview-loading" role="status"><LoaderCircle class="spin" :size="15" />Projecten laden...</p>
        <AppDialog v-model:visible="placeholderVisible" :title="placeholderTitles[placeholder?.action] || 'Afdrukken'">
            <template v-if="placeholder">
                <p>{{ placeholderTitles[placeholder.action] }} is nog niet beschikbaar.</p>
                <dl class="overview-placeholder-details">
                    <dt>Project</dt><dd>{{ placeholder.project.reference || placeholder.project.id }}</dd>
                    <template v-if="placeholder.action === 'labels'"><dt>Inzetdag</dt><dd>{{ placeholder.labelDate }}</dd></template>
                </dl>
            </template>
            <template #footer><button class="button" type="button" @click="placeholderVisible = false">Sluiten</button></template>
        </AppDialog>
    </section>
</template>

<style scoped>
.project-overview { min-width:0; }
.overview-count { margin-left:auto; color:var(--muted); font-weight:400; }
.overview-label-date { display:flex; align-items:center; flex-wrap:wrap; gap:8px 12px; }
.overview-label-date input { min-height:35px; padding:6px 9px; border:1px solid #9eabb2; border-radius:2px; background:var(--surface); color:var(--ink); font:inherit; }
:deep(.project-overview-table) { min-width:1080px; }
:deep(.project-overview-table th) { white-space:normal; }
:deep(.project-overview-table th),:deep(.project-overview-table td) { padding:10px 12px; }
.overview-project { display:grid; gap:4px; min-width:160px; }
.overview-project span { overflow-wrap:anywhere; }
.overview-status { display:inline-block; max-width:155px; padding:3px 6px; border:1px solid var(--line); font-size:11px; overflow-wrap:anywhere; }
.overview-status.neutral { background:var(--surface-alt); color:var(--muted); }
.overview-status.running { border-color:#a8c9d3; background:var(--accent-faint); color:var(--accent); }
.overview-status.ready { border-color:#9dc8aa; background:#edf6ef; color:#24573d; }
.overview-status.blocked { border-color:#d5a452; background:#fff3cf; color:#714d00; }
.overview-sample-type { display:flex; align-items:center; gap:6px; white-space:nowrap; }
.overview-sample-type svg { flex:none; color:var(--accent); }
.overview-progress { display:grid; gap:4px; width:100px; }
.overview-progress progress { width:100%; height:12px; accent-color:var(--accent); }
.overview-assigned { display:flex; justify-content:center; color:#247448; }
.overview-deadline.warning { color:#9a5500; font-weight:700; }
.overview-deadline.danger { color:#b14535; font-weight:700; }
.overview-actions { display:flex; justify-content:flex-end; gap:4px; min-width:94px; }
.overview-actions .icon-button { display:grid; place-items:center; text-decoration:none; flex:none; }
.overview-load-sentinel { height:2px; }
.overview-loading,.overview-load-error { display:flex; align-items:center; gap:8px; margin:0; }
.overview-loading { color:var(--muted); font-size:12px; }
.overview-placeholder-details { display:grid; grid-template-columns:80px minmax(0,1fr); gap:8px 12px; font-size:12px; }
.overview-placeholder-details dt { color:var(--muted); }
.overview-placeholder-details dd { margin:0; overflow-wrap:anywhere; }
@media(max-width:700px) { .project-overview h2 { flex-wrap:wrap; } .overview-count { margin-left:0; } }
</style>