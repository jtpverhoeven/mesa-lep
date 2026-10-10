<script setup>
import { computed, ref } from 'vue';
import { Building2, Check, ChevronDown, CloudUpload, Download, ExternalLink, Flame, FlaskConical, Lock, Mail, Printer, Settings, StickyNote, ThumbsDown, ThumbsUp, Trash2, Unlock } from '@lucide/vue';
import Menu from 'openvue/menu';
import { useProjectSearchStore } from '../stores/projectSearchStore';
import AppDialog from './AppDialog.vue';

const props = defineProps({
    canManageAuthorization: { type: Boolean, default: false },
});
const store = useProjectSearchStore();
const reportMenu = ref(null);
const projectMenu = ref(null);
const reportMenuOpen = ref(false);
const projectMenuOpen = ref(false);
const authorizationPrompt = ref(null);
const deauthorizationDialogOpen = ref(false);
const deauthorizationOrigin = ref('');
const deauthorizationReason = ref('');
const deauthorizationError = ref('');
const authorizationChecks = computed(() => authorizationPrompt.value?.assessment?.checks.filter((check) => !check.passed) ?? []);

const reportMenuItems = [
    { label: 'Rapport genereren', icon: ExternalLink },
    { label: 'Voorlopig rapport genereren', icon: ExternalLink },
    { separator: true },
    { label: 'Monster bestanden mailen', icon: Mail },
    { separator: true },
    { label: 'Data uitvoeren', icon: Download },
];

const projectMenuItems = computed(() => {
    const items = [];
    const project = store.projectData?.project;
    const isAuthorized = project && Number(project.auth_status) !== 0;

    if (props.canManageAuthorization && project) {
        if (isAuthorized) {
            items.push({
                label: 'Deautoriseren',
                icon: ThumbsDown,
                disabled: store.projectActionLoading,
                command: openDeauthorizationDialog,
            });
        } else {
            const disabled = Number(project.locked) !== 0 || store.projectActionLoading;
            items.push(
                { label: 'Autoriseren', icon: ThumbsUp, disabled, command: () => requestAuthorization(false) },
                { label: 'Stille autorisatie', icon: CloudUpload, disabled, command: () => requestAuthorization(true) },
            );
        }

        items.push({ separator: true });
    }

    items.push(
    { label: 'Portal sync forceren', icon: CloudUpload },
    { label: 'Resultaten uit portal halen', icon: Flame },
    { separator: true },
    { label: 'Klant wijzigen', icon: Building2 },
    { separator: true },
    { label: 'Project verwijderen', icon: Trash2 },
    { separator: true },
    { label: 'Project autorisatie blokkeren', icon: Lock },
    { label: 'Project autorisatie vrijgeven', icon: Unlock },
    );

    return items;
});

function toggleReportMenu(event) {
    const menu = Array.isArray(reportMenu.value) ? reportMenu.value[0] : reportMenu.value;
    menu?.toggle(event);
}

function toggleProjectMenu(event) {
    const menu = Array.isArray(projectMenu.value) ? projectMenu.value[0] : projectMenu.value;
    menu?.toggle(event);
}

async function requestAuthorization(quiet) {
    store.projectActionError = '';
    const result = await store.authorizeProject({ quiet });

    if (!result || Number(result.project?.id) !== Number(store.selectedProjectId)) return;
    if (result.authorized) {
        if (result.portal_sync_failed) {
            store.projectActionError = 'Project geautoriseerd, maar synchronisatie met het klantenportaal is mislukt.';
        }
        return;
    }

    if (result.assessment) {
        authorizationPrompt.value = { ...result, quiet };
        return;
    }

    store.projectActionError = result.message || 'Het project kon niet worden geautoriseerd.';
}

async function confirmAuthorization() {
    if (!authorizationPrompt.value || authorizationPrompt.value.blocked) return;

    const quiet = authorizationPrompt.value.quiet;
    const result = await store.authorizeProject({ quiet, overrideIncomplete: true });

    if (!result || Number(result.project?.id) !== Number(store.selectedProjectId)) return;
    if (result.authorized) {
        authorizationPrompt.value = null;
        if (result.portal_sync_failed) {
            store.projectActionError = 'Project geautoriseerd, maar synchronisatie met het klantenportaal is mislukt.';
        }
    } else if (result.assessment) {
        authorizationPrompt.value = { ...result, quiet };
    } else {
        store.projectActionError = result.message || 'Het project kon niet worden geautoriseerd.';
        authorizationPrompt.value = null;
    }
}

function openDeauthorizationDialog() {
    deauthorizationOrigin.value = '';
    deauthorizationReason.value = '';
    deauthorizationError.value = '';
    deauthorizationDialogOpen.value = true;
}

async function submitDeauthorization() {
    const reason = deauthorizationReason.value.trim();

    if (!deauthorizationOrigin.value || !reason) {
        deauthorizationError.value = 'Selecteer een bron en voer een reden in.';
        return;
    }

    const result = await store.deauthorizeProject({ origin: deauthorizationOrigin.value, reason });

    if (result?.deauthorized) {
        deauthorizationDialogOpen.value = false;
        deauthorizationError.value = '';
        if (result.portal_sync_failed) {
            store.projectActionError = 'Project gedeautoriseerd, maar synchronisatie met het klantenportaal is mislukt.';
        }
    } else {
        deauthorizationError.value = result?.message || store.projectActionError || 'Het project kon niet worden gedeautoriseerd.';
    }
}

function updateAuthorizationPrompt(visible) {
    if (!visible && !store.projectActionLoading) authorizationPrompt.value = null;
}

function updateDeauthorizationDialog(visible) {
    if (!visible && !store.projectActionLoading) deauthorizationDialogOpen.value = false;
}
</script>

<template>
    <section class="sample-panel project-samples-panel">
        <h2>
            <span class="project-samples-title"><FlaskConical :size="16" />Projectinhoud</span>
            <span class="project-sample-actions">
                <span class="project-sample-menu-group">
                    <button class="project-sample-menu-trigger" type="button" aria-label="Rapportage-opties openen" title="Rapportage-opties" aria-haspopup="menu" :aria-expanded="reportMenuOpen" aria-controls="project-report-menu" @click="toggleReportMenu">
                        <Printer :size="14" aria-hidden="true" />
                        <ChevronDown :size="12" aria-hidden="true" />
                    </button>
                    <Menu id="project-report-menu" ref="reportMenu" class="project-sample-menu" :model="reportMenuItems" popup aria-label="Rapportage-opties" @show="reportMenuOpen = true" @hide="reportMenuOpen = false">
                        <template #item="{ item, props: itemProps }">
                            <a v-bind="itemProps.action">
                                <component :is="item.icon" :size="15" aria-hidden="true" />
                                <span v-bind="itemProps.label">{{ item.label }}</span>
                            </a>
                        </template>
                    </Menu>
                </span>
                <span class="project-sample-menu-group">
                    <button class="project-sample-menu-trigger" type="button" aria-label="Projectopties openen" title="Projectopties" aria-haspopup="menu" :aria-expanded="projectMenuOpen" aria-controls="project-actions-menu" @click="toggleProjectMenu">
                        <Settings :size="14" aria-hidden="true" />
                        <ChevronDown :size="12" aria-hidden="true" />
                    </button>
                    <Menu id="project-actions-menu" ref="projectMenu" class="project-sample-menu" :model="projectMenuItems" popup aria-label="Projectopties" @show="projectMenuOpen = true" @hide="projectMenuOpen = false">
                        <template #item="{ item, props: itemProps }">
                            <a v-bind="itemProps.action">
                                <component :is="item.icon" :size="15" aria-hidden="true" />
                                <span v-bind="itemProps.label">{{ item.label }}</span>
                            </a>
                        </template>
                    </Menu>
                </span>
            </span>
        </h2>
        <p v-if="store.projectActionError" class="project-action-error" role="alert">{{ store.projectActionError }}</p>
        <div v-if="store.loadingProject" class="empty-state" role="status">Monsters laden...</div>
        
        <div v-else-if="store.projectData?.samples.length" class="project-sample-list">
            <button v-for="sample in store.projectData.samples" :key="sample.id" type="button" :aria-pressed="store.selectedSampleId === sample.id" @click="store.selectSample(sample.id)">
                <span class="project-sample-follow">{{ sample.follow }}.</span>
                <span class="project-sample-copy"><strong>{{ sample.description || 'Geen omschrijving' }}</strong><small>{{ sample.barcode }} · {{ sample.analyses_count }} analyses</small></span>
                <span class="project-sample-flags"><StickyNote v-if="sample.sample_note" :size="14" aria-label="Notitie aanwezig" /><Check v-if="sample.is_ready" :size="15" aria-label="Gereed" /></span>
            </button>
        </div>
        <p v-else-if="store.projectData" class="empty-state">Dit project bevat geen monsters.</p>
        <p v-else class="empty-state">Projectmonsters verschijnen hier.</p>
    </section>

    <AppDialog
        :visible="authorizationPrompt !== null"
        :title="authorizationPrompt?.blocked ? 'Project kan niet worden geautoriseerd' : 'Project autoriseren'"
        :dismissable-mask="!store.projectActionLoading"
        :close-on-escape="!store.projectActionLoading"
        :closable="!store.projectActionLoading"
        @update:visible="updateAuthorizationPrompt"
    >
        <p v-if="authorizationPrompt?.blocked" class="project-auth-blocked">Dit project kan niet worden geautoriseerd.</p>
        <p v-else>Niet alle controles zijn geslaagd. Wilt u het project toch autoriseren?</p>
        <p v-if="store.projectActionError" class="project-action-error" role="alert">{{ store.projectActionError }}</p>
        <ul class="project-auth-checks">
            <li v-for="check in authorizationChecks" :key="check.key">{{ check.message }}</li>
        </ul>
        <template #footer>
            <button class="button" type="button" :disabled="store.projectActionLoading" @click="updateAuthorizationPrompt(false)">
                {{ authorizationPrompt?.blocked ? 'Sluiten' : 'Annuleren' }}
            </button>
            <button v-if="authorizationPrompt?.requires_confirmation" class="button primary" type="button" :disabled="store.projectActionLoading" @click="confirmAuthorization">
                {{ store.projectActionLoading ? 'Bezig...' : 'Toch autoriseren' }}
            </button>
        </template>
    </AppDialog>

    <AppDialog
        :visible="deauthorizationDialogOpen"
        title="Project deautoriseren"
        :dismissable-mask="!store.projectActionLoading"
        :close-on-escape="!store.projectActionLoading"
        :closable="!store.projectActionLoading"
        @update:visible="updateDeauthorizationDialog"
    >
        <form id="project-deauthorization-form" class="project-authorization-form" @submit.prevent="submitDeauthorization">
            <label class="project-authorization-field" for="project-deauthorization-origin">
                Bron van de-authorisatie oorzaak
                <select id="project-deauthorization-origin" v-model="deauthorizationOrigin" required>
                    <option value="">Selecteer een optie</option>
                    <option value="Intern (oorzaak/fout bij MAZ)">Intern (oorzaak/fout bij MAZ)</option>
                    <option value="Extern (oorzaak/fout bij klant)">Extern (oorzaak/fout bij klant)</option>
                </select>
            </label>
            <label class="project-authorization-field" for="project-deauthorization-reason">
                Reden van de-authorisatie
                <input id="project-deauthorization-reason" v-model="deauthorizationReason" type="text" required>
            </label>
            <p v-if="deauthorizationError" class="project-action-error" role="alert">{{ deauthorizationError }}</p>
        </form>
        <template #footer>
            <button class="button" type="button" :disabled="store.projectActionLoading" @click="updateDeauthorizationDialog(false)">Annuleren</button>
            <button class="button primary" type="submit" form="project-deauthorization-form" :disabled="store.projectActionLoading">
                {{ store.projectActionLoading ? 'Bezig...' : 'Deautoriseren' }}
            </button>
        </template>
    </AppDialog>
</template>

<style scoped>
.project-samples-title { display:flex; align-items:center; gap:7px; min-width:0; }
.project-action-error,.project-auth-blocked { margin:8px 0; color:var(--danger, #ad3232); font-size:12px; }
.project-auth-checks { display:grid; gap:7px; margin:12px 0 0; padding-left:20px; }
.project-authorization-form { display:grid; gap:14px; }
.project-authorization-field { display:grid; gap:6px; color:var(--muted); font-size:12px; }
.project-authorization-field input,.project-authorization-field select { width:100%; min-height:36px; padding:7px 9px; border:1px solid var(--line); border-radius:2px; background:var(--surface); color:var(--ink); font:inherit; }
.project-sample-actions { display:flex; align-items:center; gap:4px; margin-left:auto; }
.project-sample-menu-group { position:relative; display:inline-flex; }
.project-sample-menu-trigger { display:inline-flex; align-items:center; justify-content:center; gap:3px; min-width:31px; min-height:27px; padding:4px 6px; border:1px solid #9eabb2; border-radius:2px; background:var(--surface); color:var(--muted); font:inherit; cursor:pointer; }
.project-sample-menu-trigger:hover,.project-sample-menu-trigger[aria-expanded='true'] { background:var(--accent-faint); color:var(--accent); }
.project-sample-menu-trigger svg { color:inherit; }
:deep(.project-sample-menu.p-menu) { min-width:250px; max-width:calc(100vw - 24px); padding:4px 0; border:1px solid var(--line); border-radius:2px; background:var(--surface); box-shadow:0 6px 16px rgba(20,35,45,.18); color:var(--ink); font-size:12px; }
:deep(.project-sample-menu .p-menu-list) { margin:0; padding:4px 0; list-style:none; }
:deep(.project-sample-menu .p-menu-item-content) { padding:0; }
:deep(.project-sample-menu .p-menu-item-link) { display:flex; align-items:flex-start; gap:8px; min-height:30px; padding:6px 10px; color:var(--ink); text-decoration:none; white-space:normal; }
:deep(.project-sample-menu .p-menu-item-link:hover),:deep(.project-sample-menu .p-menu-item-link:focus) { background:var(--accent-faint); color:var(--accent); }
:deep(.project-sample-menu .p-menu-item-link svg) { flex:none; margin-top:1px; }
:deep(.project-sample-menu .p-menu-separator) { margin:4px 0; border-top:1px solid var(--line); }
</style>