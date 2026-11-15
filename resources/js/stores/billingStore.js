import { defineStore } from 'pinia';
import { sfx } from '../sfx.js';

let generation = 0;

const emptyOverview = () => ({ client: null, project_rows: [], sample_types: [], articles: [] });

const isoDate = (date) => date instanceof Date && !Number.isNaN(date.getTime())
    ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
    : '';

export const billingStatuses = [
    { value: '0', label: 'Nog te factureren' },
    { value: '1', label: 'Gefactureerd' },
    { value: 'all', label: 'Alles' },
];

export const useBillingStore = defineStore('billing', {
    state: () => ({
        endpoints: {},
        csrf: '',
        projects: [],
        overview: emptyOverview(),
        loading: false,
        notice: '',
        client: null,
        dateFrom: null,
        dateTo: null,
        rangeStatus: '0',
        scanStatus: '0',
        activeBillingStatus: '0',
    }),
    getters: {
        exportUrl(state) {
            if (!state.projects.length) return null;
            const url = new URL(state.endpoints.export, window.location.origin);
            state.projects.forEach((id) => url.searchParams.append('projects[]', String(id)));
            return url.toString();
        },
    },
    actions: {
        configure(endpoints, csrf) {
            this.endpoints = endpoints;
            this.csrf = csrf;
        },
        async request(url, method, body) {
            const response = await fetch(url, {
                method, credentials: 'same-origin',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify(body),
            });
            if (response.redirected || [401, 419].includes(response.status)) throw new Error('Je sessie is verlopen. Vernieuw deze pagina.');
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw Object.assign(new Error(payload.message || ''), { payload });

            return payload;
        },
        /**
         * Port of legacy scan2bill loadOverview: resolves to the response status so the page can ask whether a
         * barcode of another client should start a new selection.
         */
        async load({ barcode = null, range = null, billingStatus = null } = {}) {
            if (billingStatus === null) billingStatus = this.activeBillingStatus;
            else this.activeBillingStatus = billingStatus;

            const request = ++generation;
            this.loading = true;
            this.notice = '';

            try {
                const payload = await this.request(this.endpoints.overview, 'POST', {
                    projects: this.projects, barcode, billing_status: billingStatus,
                    client: range?.client ?? null, date_from: range?.dateFrom ?? null, date_to: range?.dateTo ?? null,
                });
                if (request !== generation) return null;

                if (payload.status === 'client_mismatch') {
                    if (!barcode) this.deny('De geselecteerde projecten zijn niet van dezelfde klant');
                    return payload.status;
                }
                if (payload.status === 'invalid_project_range') {
                    this.notice = 'Selecteer een geldige datum van en tot';
                    return payload.status;
                }
                if (payload.status === 'no_projects' && range) this.notice = 'Geen projecten gevonden voor deze klant en periode';
                if (payload.status === 'barcode_not_found') this.deny('Barcode niet gevonden');
                if (payload.status === 'barcode_filtered') this.notice = 'Project valt buiten de gekozen facturatiestatus';

                this.projects = payload.projects ?? [];
                this.overview = payload;

                if (barcode && payload.status !== 'no_projects' && payload.client?.id) {
                    this.client = { id: payload.client.id, name: payload.client.name };
                }

                return payload.status;
            } catch (error) {
                if (request === generation) this.deny(error.message || 'Het facturatieoverzicht kon niet worden geladen.');
                return null;
            } finally {
                if (request === generation) this.loading = false;
            }
        },
        scan(barcode) {
            void sfx.play('do').catch(() => {});
            return this.load({ barcode: barcode || null, billingStatus: this.scanStatus });
        },
        startNewSelection(barcode) {
            this.projects = [];
            return this.load({ barcode });
        },
        searchRange() {
            const range = { client: this.client?.id ?? null, dateFrom: isoDate(this.dateFrom), dateTo: isoDate(this.dateTo) };
            if (!range.client || !range.dateFrom || !range.dateTo) {
                this.notice = 'Selecteer een klant en een datum van en tot';
                return null;
            }

            return this.load({ range, billingStatus: this.rangeStatus });
        },
        changeClient(client) {
            const changed = (this.client?.id ?? null) !== (client?.id ?? null);
            this.client = client;
            if (changed) {
                this.projects = [];
                return this.load();
            }
            return null;
        },
        drop(projectId) {
            this.projects = this.projects.filter((id) => id !== projectId);
            return this.load();
        },
        reset() {
            this.projects = [];
            this.client = null;
            this.dateFrom = null;
            this.dateTo = null;
            this.scanStatus = '0';
            this.rangeStatus = '0';
            return this.load({ billingStatus: '0' });
        },
        async markBilled() {
            if (!this.projects.length) {
                this.notice = 'Geen projecten geselecteerd';
                return;
            }

            try {
                const payload = await this.request(this.endpoints.markBilled, 'POST', { projects: this.projects });
                if (payload.status === 'ok') {
                    this.overview.project_rows.forEach((row) => { row.billed = true; });
                    return;
                }
                this.notice = payload.status === 'no_projects' ? 'Geen projecten geselecteerd' : 'Projecten konden niet als gefactureerd worden ingesteld';
            } catch {
                this.notice = 'Projecten konden niet als gefactureerd worden ingesteld';
            }
        },
        async setBilled(row, billed) {
            try {
                const payload = await this.request(this.endpoints.setBilled, 'PATCH', { project: row.id, billed: billed ? '1' : '0' });
                if (payload.status === 'ok') {
                    row.billed = Number(payload.billed) === 1;
                    return;
                }
                this.notice = 'De facturatiestatus kon niet worden aangepast';
            } catch {
                this.notice = 'De facturatiestatus kon niet worden aangepast';
            }
        },
        deny(message) {
            this.notice = message;
            void sfx.play('error').catch(() => {});
        },
    },
});
