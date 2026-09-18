<script setup>
import { ref } from 'vue';
import { Building2, Check, ChevronDown, CloudUpload, Download, ExternalLink, Flame, FlaskConical, Lock, Mail, Printer, Settings, StickyNote, ThumbsDown, ThumbsUp, Trash2, Unlock } from '@lucide/vue';
import Menu from 'openvue/menu';
import { useProjectSearchStore } from '../stores/projectSearchStore';

const store = useProjectSearchStore();
const reportMenu = ref(null);
const projectMenu = ref(null);
const reportMenuOpen = ref(false);
const projectMenuOpen = ref(false);

const reportMenuItems = [
    { label: 'Rapport genereren', icon: ExternalLink },
    { label: 'Voorlopig rapport genereren', icon: ExternalLink },
    { separator: true },
    { label: 'Monster bestanden mailen', icon: Mail },
    { separator: true },
    { label: 'Data uitvoeren', icon: Download },
];

const projectMenuItems = [
    { label: 'Autoriseren', icon: ThumbsUp },
    { label: 'Deautoriseren', icon: ThumbsDown },
    { separator: true },
    { label: 'Stille autorisatie', icon: CloudUpload },
    { label: 'Portal sync forceren', icon: CloudUpload },
    { label: 'Resultaten uit portal halen', icon: Flame },
    { separator: true },
    { label: 'Klant wijzigen', icon: Building2 },
    { separator: true },
    { label: 'Project verwijderen', icon: Trash2 },
    { separator: true },
    { label: 'Project autorisatie blokkeren', icon: Lock },
    { label: 'Project autorisatie vrijgeven', icon: Unlock },
];

function toggleReportMenu(event) {
    const menu = Array.isArray(reportMenu.value) ? reportMenu.value[0] : reportMenu.value;
    menu?.toggle(event);
}

function toggleProjectMenu(event) {
    const menu = Array.isArray(projectMenu.value) ? projectMenu.value[0] : projectMenu.value;
    menu?.toggle(event);
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
</template>

<style scoped>
.project-samples-title { display:flex; align-items:center; gap:7px; min-width:0; }
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