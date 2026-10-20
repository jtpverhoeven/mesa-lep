<script setup>
import { computed, reactive, ref } from 'vue';
import Avatar from 'openvue/avatar';
import Button from 'openvue/button';
import InputText from 'openvue/inputtext';
import Select from 'openvue/select';
import { Fingerprint, Image, KeyRound, PenLine, UserRound } from '@lucide/vue';
import AvatarCropper from './AvatarCropper.vue';

const props = defineProps({
    account: { type: Object, required: true },
    profileUrl: String, aliasUrl: String, passwordUrl: String,
    imageUrls: { type: Object, required: true }, csrf: String,
});
const profile = reactive({
    title: '', first_name: '', last_name: '', gender: null, job_title: '', phone_number: '',
    ...props.account.profile,
});
const displayName = ref(props.account.name);
const alias = ref(props.account.alias ?? '');
const images = reactive({ ...props.account.images });
const passwords = reactive({ current_password: '', password: '', password_confirmation: '' });
const states = reactive(Object.fromEntries(['profile', 'alias', 'avatar', 'nl', 'en', 'password'].map(key => [key, { busy: false, message: '', errors: {}, failed: false }])));
const cropFile = ref(null);
const signatureFiles = reactive({ nl: null, en: null });
const sections = [
    { id: 'personal', label: 'Persoonlijke gegevens', icon: UserRound },
    { id: 'alias', label: 'Systeemalias', icon: Fingerprint },
    { id: 'avatar', label: 'Profielfoto', icon: Image },
    { id: 'signatures', label: 'Handtekeningen', icon: PenLine },
    { id: 'password', label: 'Wachtwoord', icon: KeyRound },
];
const fields = [
    { key: 'title', label: 'Titel', max: 50, autocomplete: 'honorific-prefix' },
    { key: 'first_name', label: 'Voornaam', max: 120, autocomplete: 'given-name', required: true },
    { key: 'last_name', label: 'Achternaam', max: 120, autocomplete: 'family-name', required: true },
    { key: 'job_title', label: 'Functie', max: 150, autocomplete: 'organization-title' },
    { key: 'phone_number', label: 'Telefoonnummer', max: 50, autocomplete: 'tel', type: 'tel' },
];
const genders = computed(() => {
    const options = [{ label: 'Niet opgegeven', value: null }, { label: 'Man', value: 'M' }, { label: 'Vrouw', value: 'F' }, { label: 'Anders', value: 'X' }];
    if (profile.gender && !options.some(option => option.value === profile.gender)) options.push({ label: profile.gender, value: profile.gender });
    return options;
});

function broadcast(detail) {
    window.dispatchEvent(new CustomEvent('mesa:account-updated', { detail }));
}

async function submit(scope, url, method, data, saved) {
    const state = states[scope];
    if (state.busy) return;
    Object.assign(state, { busy: true, message: '', errors: {}, failed: false });
    try {
        const multipart = data instanceof FormData;
        const response = await fetch(url, {
            method,
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': props.csrf, ...(!multipart && data ? { 'Content-Type': 'application/json' } : {}) },
            body: data ? (multipart ? data : JSON.stringify(data)) : undefined,
        });
        if (response.redirected || [401, 419].includes(response.status)) {
            throw new Error('Uw sessie is verlopen. Vernieuw de pagina en log opnieuw in.');
        }
        if (!response.headers.get('content-type')?.includes('application/json')) {
            throw new Error('De server gaf een onverwacht antwoord. Vernieuw de pagina en probeer opnieuw.');
        }
        const result = await response.json();
        if (!response.ok) {
            state.errors = result.errors ?? {};
            throw new Error(response.status >= 500 ? 'Opslaan is mislukt. Probeer opnieuw of neem contact op met de beheerder.'
                : result.message ?? 'De wijziging kon niet worden opgeslagen.');
        }
        saved?.(result);
        state.message = result.message;
    } catch (error) {
        state.failed = true;
        state.message = error.message || 'Geen verbinding met de server. Probeer opnieuw.';
    } finally {
        state.busy = false;
    }
}

function saveProfile() {
    return submit('profile', props.profileUrl, 'PUT', profile, result => {
        displayName.value = result.name;
        broadcast({ name: result.name });
    });
}

function selectAvatar(event) {
    const file = event.target.files[0];
    event.target.value = '';
    states.avatar.message = '';
    states.avatar.errors = {};
    states.avatar.failed = false;
    if (!file) return;
    if (!['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
        states.avatar.failed = true;
        states.avatar.message = 'Kies een JPG, PNG, GIF of WebP van maximaal 5 MB.';
        return;
    }
    cropFile.value = file;
}

function uploadImage(kind, file) {
    const data = new FormData();
    data.append('image', file);
    return submit(kind, props.imageUrls[kind], 'POST', data, result => {
        images[kind] = `${result.url}?v=${Date.now()}`;
        if (kind === 'avatar') {
            broadcast({ avatar: images.avatar });
            cropFile.value = null;
        } else {
            signatureFiles[kind] = null;
        }
    });
}

function removeImage(kind) {
    return submit(kind, props.imageUrls[kind], 'DELETE', null, () => {
        images[kind] = null;
        if (kind === 'avatar') {
            broadcast({ avatar: null });
        } else {
            signatureFiles[kind] = null;
        }
    });
}
</script>

<template>
    <div class="account-settings">
        <nav class="account-settings-nav" aria-label="Accountinstellingen">
            <a v-for="section in sections" :key="section.id" :href="`#${section.id}`"><component :is="section.icon" :size="17" aria-hidden="true" />{{ section.label }}</a>
        </nav>
        <div class="grid min-w-0 gap-6">
            <section id="personal" class="settings-panel" aria-labelledby="personal-heading">
                <div class="flex items-center gap-4">
                    <Avatar :image="images.avatar" :label="images.avatar ? undefined : displayName.charAt(0).toUpperCase()" size="xlarge" shape="circle" aria-hidden="true" />
                    <div><h2 id="personal-heading">Persoonlijke gegevens</h2><p class="text-muted">Beheer uw contactgegevens en hoe uw naam wordt weergegeven.</p></div>
                </div>
                <form class="grid gap-5" @submit.prevent="saveProfile">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div v-for="field in fields" :key="field.key" class="field">
                            <label :for="`profile-${field.key}`">{{ field.label }}</label>
                            <InputText :id="`profile-${field.key}`" v-model="profile[field.key]" :type="field.type ?? 'text'" :maxlength="field.max" :required="field.required" :autocomplete="field.autocomplete" :invalid="Boolean(states.profile.errors[field.key])" :aria-describedby="states.profile.errors[field.key] ? `${field.key}-error` : undefined" />
                            <small v-if="states.profile.errors[field.key]" :id="`${field.key}-error`" class="text-danger">{{ states.profile.errors[field.key][0] }}</small>
                        </div>
                        <div class="field">
                            <label for="profile-gender">Geslacht</label>
                            <Select input-id="profile-gender" v-model="profile.gender" :options="genders" option-label="label" option-value="value" :invalid="Boolean(states.profile.errors.gender)" />
                        </div>
                        <div class="field sm:col-span-2">
                            <label for="profile-email">E-mailadres</label>
                            <InputText id="profile-email" :model-value="account.email" readonly aria-describedby="email-help" />
                            <small id="email-help" class="text-muted">Uw e-mailadres wordt gebruikt om in te loggen en kan hier niet worden gewijzigd.</small>
                        </div>
                    </div>
                    <p v-if="states.profile.message" :class="states.profile.failed ? 'text-danger' : 'text-accent'" role="status">{{ states.profile.message }}</p>
                    <div><Button type="submit" :loading="states.profile.busy" :disabled="states.profile.busy">Gegevens opslaan</Button></div>
                </form>
            </section>

            <section id="alias" class="settings-panel" aria-labelledby="alias-heading">
                <div><h2 id="alias-heading">Systeemalias</h2><p class="text-muted">Een unieke, korte naam waarmee u ook kunt inloggen. Laat leeg om de alias te verwijderen.</p></div>
                <form class="grid gap-4" @submit.prevent="submit('alias', aliasUrl, 'PUT', { alias }, result => alias = result.alias ?? '')">
                    <div class="field">
                        <label for="account-alias">Alias</label>
                        <InputText id="account-alias" v-model="alias" maxlength="255" autocomplete="username" :invalid="Boolean(states.alias.errors.alias)" aria-describedby="alias-feedback" />
                        <small v-if="states.alias.errors.alias" class="text-danger">{{ states.alias.errors.alias[0] }}</small>
                    </div>
                    <p id="alias-feedback" :class="states.alias.failed ? 'text-danger' : 'text-accent'" role="status">{{ states.alias.message }}</p>
                    <div><Button type="submit" :loading="states.alias.busy" :disabled="states.alias.busy">Alias opslaan</Button></div>
                </form>
            </section>

            <section id="avatar" class="settings-panel" aria-labelledby="avatar-heading">
                <div><h2 id="avatar-heading">Profielfoto</h2><p class="text-muted">Kies een foto en snijd deze bij. Opgeslagen als 256 × 256 pixels.</p></div>
                <div class="flex flex-wrap items-center gap-5">
                    <Avatar :image="images.avatar" :label="images.avatar ? undefined : displayName.charAt(0).toUpperCase()" size="xlarge" shape="circle" aria-label="Huidige profielfoto" />
                    <div class="field flex-1">
                        <label for="avatar-upload">Nieuwe afbeelding</label>
                        <input id="avatar-upload" type="file" accept="image/jpeg,image/png,image/gif,image/webp" :disabled="states.avatar.busy" @change="selectAvatar">
                        <small class="text-muted">JPG, PNG, GIF of WebP. Maximaal 5 MB en 4096 × 4096 pixels.</small>
                    </div>
                    <Button v-if="images.avatar" severity="danger" variant="outlined" :disabled="states.avatar.busy" @click="removeImage('avatar')">Verwijderen</Button>
                </div>
                <p v-if="states.avatar.message" :class="states.avatar.failed ? 'text-danger' : 'text-accent'" role="status">{{ states.avatar.message }}</p>
                <AvatarCropper v-if="cropFile" :file="cropFile" :busy="states.avatar.busy" @close="cropFile = null" @save="uploadImage('avatar', $event)">
                    <p v-if="states.avatar.failed" class="text-danger" role="alert">{{ states.avatar.errors.image?.[0] ?? states.avatar.message }}</p>
                </AvatarCropper>
            </section>

            <section id="signatures" class="settings-panel" aria-labelledby="signatures-heading">
                <div><h2 id="signatures-heading">Handtekeningen</h2><p class="text-muted">Upload afzonderlijke handtekeningen voor Nederlandse en Engelse documenten. Transparante PNG heeft de voorkeur.</p></div>
                <div class="grid gap-6 sm:grid-cols-2">
                    <form v-for="language in ['nl', 'en']" :key="language" class="grid content-start gap-4" @submit.prevent="uploadImage(language, signatureFiles[language])">
                        <h3 class="font-semibold">{{ language === 'nl' ? 'Nederlands' : 'Engels' }}</h3>
                        <div class="signature-preview">
                            <img v-if="images[language]" :src="images[language]" :alt="`${language === 'nl' ? 'Nederlandse' : 'Engelse'} handtekening`">
                            <span v-else class="text-muted">Nog geen handtekening</span>
                        </div>
                        <div class="field">
                            <label :for="`signature-${language}`">Nieuwe handtekening</label>
                            <input :id="`signature-${language}`" :key="images[language]" type="file" accept="image/jpeg,image/png,image/gif,image/webp" :disabled="states[language].busy" @change="signatureFiles[language] = $event.target.files[0] ?? null">
                            <small class="text-muted">Maximaal 5 MB en 4096 × 4096 pixels.</small>
                            <small v-if="states[language].errors.image" class="text-danger">{{ states[language].errors.image[0] }}</small>
                        </div>
                        <p v-if="states[language].message" :class="states[language].failed ? 'text-danger' : 'text-accent'" role="status">{{ states[language].message }}</p>
                        <div class="flex flex-wrap gap-2">
                            <Button type="submit" :disabled="!signatureFiles[language] || states[language].busy" :loading="states[language].busy">Uploaden</Button>
                            <Button v-if="images[language]" severity="danger" variant="outlined" :disabled="states[language].busy" @click="removeImage(language)">Verwijderen</Button>
                        </div>
                    </form>
                </div>
            </section>

            <section id="password" class="settings-panel" aria-labelledby="password-heading">
                <div><h2 id="password-heading">Wachtwoord wijzigen</h2><p class="text-muted">Bevestig uw huidige wachtwoord en kies een nieuw wachtwoord van minimaal 8 tekens.</p></div>
                <form class="grid gap-4" @submit.prevent="submit('password', passwordUrl, 'PUT', passwords, () => Object.keys(passwords).forEach(key => passwords[key] = ''))">
                    <div v-for="field in [{ key: 'current_password', label: 'Huidig wachtwoord' }, { key: 'password', label: 'Nieuw wachtwoord' }, { key: 'password_confirmation', label: 'Herhaal nieuw wachtwoord' }]" :key="field.key" class="field">
                        <label :for="`account-${field.key}`">{{ field.label }}</label>
                        <InputText :id="`account-${field.key}`" v-model="passwords[field.key]" type="password" required :autocomplete="field.key === 'current_password' ? 'current-password' : 'new-password'" :invalid="Boolean(states.password.errors[field.key])" :aria-describedby="states.password.errors[field.key] ? `${field.key}-error` : undefined" />
                        <small v-if="states.password.errors[field.key]" :id="`${field.key}-error`" class="text-danger">{{ states.password.errors[field.key][0] }}</small>
                    </div>
                    <p v-if="states.password.message" :class="states.password.failed ? 'text-danger' : 'text-accent'" role="status">{{ states.password.message }}</p>
                    <div><Button type="submit" :loading="states.password.busy" :disabled="states.password.busy">Wachtwoord wijzigen</Button></div>
                </form>
            </section>
        </div>
    </div>
</template>
