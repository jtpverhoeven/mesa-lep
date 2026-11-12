import { defineStore } from 'pinia';
import { rejectNotEditable } from './keyringStore.js';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export const useNoteStore = defineStore('notes', {
    state: () => ({ endpoints: {}, permissions: {}, users: [], usersLoaded: false, templates: [], templatesLoaded: false }),
    actions: {
        configure({ endpoints, permissions }) {
            this.endpoints = endpoints;
            this.permissions = permissions;
        },
        endpoint(name, replacements = {}) {
            return Object.entries(replacements).reduce(
                (url, [key, value]) => url.replace(`__${key.toUpperCase()}__`, value),
                this.endpoints[name],
            );
        },
        async request(method, url, body = undefined, lockTarget = null) {
            const response = await fetch(url, {
                method,
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    ...(body === undefined ? {} : { 'Content-Type': 'application/json' }),
                },
                body: body === undefined ? undefined : JSON.stringify(body),
            });
            const payload = await response.json();
            rejectNotEditable(response, payload, lockTarget);
            if (!response.ok) {
                throw new Error(Object.values(payload.errors ?? {}).flat().join(' ') || payload.message || 'De notitie kon niet worden opgeslagen.');
            }

            return payload.data;
        },
        async loadUsers() {
            if (this.usersLoaded) return;
            this.users = await this.request('GET', this.endpoints.users);
            this.usersLoaded = true;
        },
        async loadTemplates() {
            if (this.templatesLoaded) return;
            this.templates = await this.request('GET', this.endpoints.templates);
            this.templatesLoaded = true;
        },
        async saveTemplate(name, content) {
            this.templates = await this.request('POST', this.endpoints.templates, { name, content });
        },
        async deleteTemplate(id) {
            this.templates = await this.request('DELETE', this.endpoint('template', { template: id }));
        },
    },
});
