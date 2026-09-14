<script setup>
import { computed, ref } from 'vue';
import { AlertCircle, CheckCircle2, ChevronDown, Eye, EyeOff, LoaderCircle, RotateCcw } from '@lucide/vue';
import DOMPurify from 'dompurify';
import Menu from 'openvue/menu';

const props = defineProps({
    calculation: { type: Object, default: null },
    confirmation: { type: Object, default: null },
    confirmationBusy: { type: Boolean, default: false },
    confirmationError: { type: String, default: '' },
    canResetConfirmation: { type: Boolean, default: false },
    readOnly: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
    emptyText: { type: String, default: 'Geen eindresultaat beschikbaar.' },
});

const emit = defineEmits(['confirmation-decision', 'open-confirmation']);
const confirmationMenu = ref(null);
const confirmationMenuOpen = ref(false);

function display(value) {
    const normalized = value !== null && typeof value === 'object' ? JSON.stringify(value) : String(value ?? '-');
    return DOMPurify.sanitize(normalized, { ALLOWED_TAGS: ['sup'], ALLOWED_ATTR: [] });
}

const hidden = computed(() => {
    const value = props.calculation?.resultHide;
    if (Array.isArray(value)) return new Set(value);
    if (value && typeof value === 'object') return new Set(Object.keys(value).filter((key) => value[key]));
    return new Set();
});

const messages = computed(() => {
    const value = props.calculation?.messageBag;
    const items = Array.isArray(value) ? value : (value ? Object.values(value) : []);
    return items.filter(Boolean).map((message) => message !== null && typeof message === 'object' ? JSON.stringify(message) : String(message));
});
const addendumMarkers = {
    indicative: '*',        
    not_confirmed: '**',    
};
function normalizeAddendum(item) {
    if (item === null || typeof item === 'undefined') return null;

    const isObject = typeof item === 'object' && !Array.isArray(item);
    const code = isObject ? String(item.code ?? '') : '';
    const label = isObject ? String(item.label ?? item.text ?? code) : String(item);
    const normalizedCode = code.toLowerCase().replaceAll('-', '_').replaceAll(' ', '_');
    const normalizedLabel = label.toLowerCase();
    const marker = addendumMarkers[normalizedCode]
        ?? (normalizedLabel === 'indicatieve waarde' || normalizedLabel === 'indicatief' ? '*' : null)
        ?? (normalizedLabel === 'niet bevestigd' || normalizedLabel === 'niet bevestigde waarde' ? '**' : null);

    if (!label && !marker) return null;

    return { marker, label: label || code };
}
const addenda = computed(() => {
    const value = props.calculation?.addenda;
    if (!value) return [];

    const items = Array.isArray(value)
        ? value
        : typeof value === 'object' && ('code' in value || 'label' in value || 'text' in value)
            ? [value]
            : typeof value === 'object'
                ? Object.values(value)
                : [value];

    return items.map(normalizeAddendum).filter(Boolean);
});

const results = computed(() => {
    const output = Object.entries(props.calculation?.output ?? {});
    const reportKey = props.calculation?.reportIn ?? output[0]?.[0];

    return output
        .filter(([key]) => !hidden.value.has(key))
        .map(([key, value]) => {
            const displayedValue = display(value ?? props.calculation?.outputEn?.[key]);
            const resultAddenda = key === reportKey
                ? addenda.value.filter((item) => !item.marker || !displayedValue.includes(`<sup>${item.marker}</sup>`))
                : [];

            return {
                key,
                label: props.calculation?.resultMask?.[key] || key,
                value: displayedValue,
                addenda: resultAddenda,
                disposition: props.calculation?.disposition?.[key] ?? null,
            };
        });
});
const confirmationDecision = computed(() => Number(props.confirmation?.decision ?? 0));
const confirmationMode = computed(() => props.confirmation?.config?.mode ?? null);
const confirmationActionsVisible = computed(() => !props.readOnly && [1, 2].includes(confirmationDecision.value));
const globalConfirmationButtonVisible = computed(() => confirmationActionsVisible.value && confirmationDecision.value === 1 && confirmationMode.value === 'global');
const confirmationMenuItems = computed(() => [
    {
        label: 'Onderdrukken (Actief niet bevestigd)',
        icon: EyeOff,
        disabled: props.confirmationBusy,
        command: () => emit('confirmation-decision', 'disable'),
    },
    {
        label: 'Reset bevestiging (Bevestiging op N.V.T.)',
        icon: RotateCcw,
        visible: props.canResetConfirmation,
        disabled: props.confirmationBusy,
        command: () => emit('confirmation-decision', 'reset'),
    },
]);
const confirmationWaiting = computed(() => props.confirmation?.status === 'enabled_pending');
const ready = computed(() => Boolean(props.calculation?.isReady) && !confirmationWaiting.value);

function toggleConfirmationMenu(event) {
    const menu = Array.isArray(confirmationMenu.value) ? confirmationMenu.value[0] : confirmationMenu.value;
    menu?.toggle(event);
}
</script>

<template>
    <div class="end-result" :aria-busy="loading" aria-live="polite">
        <div v-if="loading" class="end-result-state" role="status">
            <LoaderCircle class="spin" :size="17" />Eindresultaat berekenen...
        </div>
        <div v-else-if="error" class="end-result-state end-result-error" role="alert">
            <AlertCircle :size="17" />
            <span>{{ error }}</span>
        </div>
        <div v-else-if="results.length" class="end-result-values">
            <div v-for="(result, index) in results" :key="result.key" class="end-result-row">
                <span class="end-result-label">{{ result.label }}</span>
                <span class="end-result-value">
                    <strong v-html="result.value"></strong>
                    <template v-for="(addendum, addendumIndex) in result.addenda" :key="`${addendum.label}-${addendumIndex}`">
                        <sup v-if="addendum.marker" class="end-result-addendum" :aria-label="addendum.label" :title="addendum.label">{{ addendum.marker }}</sup>
                        <span v-else class="end-result-addendum-text">{{ addendum.label }}</span>
                    </template>
                </span>
                <div v-if="result.disposition || (index === 0 && confirmationActionsVisible)" class="end-result-row-actions">
                    <span v-if="result.disposition" class="end-result-disposition" :title="`Dispositie: ${result.disposition}`">{{ result.disposition }}</span>
                    <div v-if="index === 0 && confirmationActionsVisible" class="end-result-confirmation">
                        <button v-if="globalConfirmationButtonVisible" class="end-result-confirmation-button end-result-confirmation-primary" type="button" :disabled="confirmationBusy" @click="emit('open-confirmation')"><Eye :size="14" />Bevestiging</button>
                        <div v-if="confirmationDecision === 1" class="end-result-confirmation-group">
                            <button class="end-result-confirmation-button end-result-confirmation-warning" type="button" :disabled="confirmationBusy" aria-haspopup="menu" :aria-expanded="confirmationMenuOpen" @click="toggleConfirmationMenu"><EyeOff :size="14" />Uitzetten<ChevronDown :size="13" /></button>
                            <Menu ref="confirmationMenu" class="end-result-confirmation-menu" :model="confirmationMenuItems" popup aria-label="Bevestigingsopties" @show="confirmationMenuOpen = true" @hide="confirmationMenuOpen = false">
                                <template #item="{ item, props: itemProps }">
                                    <a v-bind="itemProps.action">
                                        <component :is="item.icon" :size="15" aria-hidden="true" />
                                        <span v-bind="itemProps.label">{{ item.label }}</span>
                                    </a>
                                </template>
                            </Menu>
                        </div>
                        <button v-else-if="confirmationDecision === 2" class="end-result-confirmation-button end-result-confirmation-success" type="button" :disabled="confirmationBusy" @click="emit('confirmation-decision', 'enable')"><Eye :size="14" />Aanzetten</button>
                    </div>
                </div>
            </div>
            <div class="end-result-status" :class="{ ready }">
                <CheckCircle2 v-if="ready" :size="15" />
                <AlertCircle v-else :size="15" />
                {{ confirmationWaiting ? 'Bevestiging wacht' : ready ? 'Gereed' : 'Nog niet gereed' }}
            </div>

            <p v-if="confirmationError" class="end-result-confirmation-error" role="alert">{{ confirmationError }}</p>
            <ul v-if="messages.length" class="end-result-messages">
                <li v-for="(message, index) in messages" :key="index">{{ message }}</li>
            </ul>
        </div>
        <p v-else class="empty-state">{{ emptyText }}</p>
    </div>
</template>

<style scoped>
.end-result { min-width:0; }
.end-result-state { display:flex; align-items:center; gap:8px; min-height:52px; color:var(--muted); }
.end-result-error { color:#903e32; }
.end-result-error span { min-width:0; flex:1; overflow-wrap:anywhere; }
.end-result-error .icon-button { flex:none; margin:0; }
.end-result-values { display:grid; gap:10px; }
.end-result-row { display:grid; grid-template-columns:minmax(75px,.7fr) minmax(0,1.4fr) auto; align-items:center; gap:9px; min-height:40px; padding-bottom:9px; border-bottom:1px solid var(--line); }
.end-result-label { color:var(--muted); overflow-wrap:anywhere; }
.end-result-value { display:inline-flex; align-items:baseline; flex-wrap:wrap; min-width:0; gap:2px; }
.end-result-row strong { font-size:15px; overflow-wrap:anywhere; }
.end-result-addendum { color:#903e32; font-size:.8em; font-weight:700; }
.end-result-addendum-text { color:#903e32; font-size:11px; font-weight:700; overflow-wrap:anywhere; }
.end-result-row-actions { display:flex; align-items:center; justify-content:flex-end; gap:8px; min-width:0; }
.end-result-disposition { display:grid; place-items:center; min-width:24px; height:24px; border:1px solid #9eabb2; background:var(--surface-alt); color:var(--ink); font-weight:700; }
.end-result-status { display:flex; align-items:center; gap:6px; color:#9a5500; font-size:11px; font-weight:700; }
.end-result-status.ready { color:#247448; }
.end-result-confirmation { display:flex; align-items:center; flex-wrap:wrap; justify-content:flex-end; gap:4px; }
.end-result-confirmation-group { position:relative; display:inline-flex; }
.end-result-confirmation-button { display:inline-flex; align-items:center; justify-content:center; gap:5px; min-height:27px; padding:3px 8px; border:1px solid transparent; border-radius:3px; color:#fff; font:inherit; font-size:11px; line-height:18px; text-shadow:0 -1px 0 rgba(0,0,0,.25); cursor:pointer; white-space:nowrap; }
.end-result-confirmation-button:hover { filter:brightness(.94); }
.end-result-confirmation-button:disabled { cursor:not-allowed; opacity:.55; }
.end-result-confirmation-primary { border-color:#0077b3; background:linear-gradient(#08c,#04c); }
.end-result-confirmation-warning { border-color:#e08a00; background:linear-gradient(#fbb450,#f89406); }
.end-result-confirmation-success { border-color:#468847; background:linear-gradient(#62c462,#51a351); }
.end-result-confirmation-error { margin:0; color:#903e32; font-size:11px; overflow-wrap:anywhere; }
.end-result-messages { display:grid; gap:5px; margin:0; padding-left:18px; color:var(--muted); font-size:11px; }
:deep(.end-result-confirmation-menu.p-menu) { min-width:270px; max-width:calc(100vw - 24px); padding:5px 0; border:1px solid #ccc; border-radius:3px; background:#fff; box-shadow:0 5px 10px rgba(0,0,0,.2); color:#333; font-size:12px; }
:deep(.end-result-confirmation-menu .p-menu-list) { margin:0; padding:5px 0; list-style:none; }
:deep(.end-result-confirmation-menu .p-menu-item-content) { padding:0; }
:deep(.end-result-confirmation-menu .p-menu-item-link) { display:flex; align-items:flex-start; gap:7px; min-height:28px; padding:5px 20px; color:#333; text-decoration:none; white-space:normal; }
:deep(.end-result-confirmation-menu .p-menu-item-link:hover),:deep(.end-result-confirmation-menu .p-menu-item-link:focus) { background:#0081c2; color:#fff; }
:deep(.end-result-confirmation-menu .p-menu-item-link svg) { flex:none; margin-top:1px; }
@media (max-width:520px) { .end-result-row-actions { grid-column:1/-1; justify-content:flex-start; } }
@media (max-width:420px) { .end-result-row { grid-template-columns:minmax(0,1fr) auto; } .end-result-label { grid-column:1/-1; } }
</style>