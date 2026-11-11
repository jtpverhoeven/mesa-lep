import { defineStore } from 'pinia';
import { useClientSelectorStore } from './clientSelectorStore';

const EARLIEST_DATE = '2000-01-01';
const LATEST_DATE = '2099-12-31';
const SEPARATOR_LABELS = { semicolon: 'puntkomma (;)', comma: 'komma (,)', tab: 'tab', pipe: 'verticale streep (|)' };
let nextRowKey = 1;

async function request(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            ...options.headers,
        },
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        const error = new Error(Object.values(data.errors ?? {}).flat()[0] ?? data.message ?? 'De bewerking kon niet worden uitgevoerd.');
        error.errors = data.errors ?? {};
        throw error;
    }

    return data;
}

export function isValidDate(value) {
    return /^\d{4}-\d{2}-\d{2}$/.test(value ?? '') && value >= EARLIEST_DATE && value <= LATEST_DATE;
}

function validateRow(row) {
    const issues = {};
    if (!isValidDate(row.sampling_date)) issues.sampling_date = 'Vul een geldige monsternamedatum in.';
    if (row.tht && !isValidDate(row.tht_date)) issues.tht_date = 'Vul de THT-datum in voor een THT-onderzoek.';
    if (![row.project_name, row.sample_name, row.sample_details].some((value) => (value ?? '').trim() !== '')) {
        issues.sample_name = 'Vul minimaal een projectnaam, monsternaam of details in.';
    }

    return issues;
}

export const useSampleBufferImportStore = defineStore('sampleBufferImport', {
    state: () => ({
        endpoints: {},
        client: null,
        file: null,
        fileInputKey: 0,
        separator: 'auto',
        preview: null,
        rows: [],
        metaColumns: [],
        previewing: false,
        submitting: false,
        error: '',
        commitErrors: [],
        result: null,
        requestId: 0,
    }),
    getters: {
        issues: (state) => Object.fromEntries(state.rows.map((row) => [row.key, validateRow(row)]).filter(([, issues]) => Object.keys(issues).length)),
        issueCount() {
            return Object.keys(this.issues).length;
        },
        alertCount: (state) => state.rows.filter((row) => row.product_group_alert).length,
        separatorLabel: (state) => SEPARATOR_LABELS[state.preview?.separator] ?? '',
        canCommit() {
            return Boolean(this.client && this.rows.length && !this.issueCount && !this.previewing && !this.submitting);
        },
    },
    actions: {
        configure(endpoints) {
            this.endpoints = endpoints;
            useClientSelectorStore().configure(endpoints.clientSearch);
        },
        selectClient(client) {
            useClientSelectorStore().select(client);
            this.client = client;
            this.result = null;
            return this.loadPreview();
        },
        clearClient() {
            useClientSelectorStore().clear();
            this.client = null;
            this.requestId++;
            this.clearPreview();
        },
        setFile(file) {
            this.file = file;
            this.result = null;
            return this.loadPreview();
        },
        setSeparator(separator) {
            this.separator = separator;
            return this.loadPreview();
        },
        clearPreview() {
            this.preview = null;
            this.rows = [];
            this.metaColumns = [];
            this.error = '';
            this.commitErrors = [];
            this.previewing = false;
        },
        async loadPreview() {
            if (!this.client || !this.file) {
                this.clearPreview();
                return;
            }

            const requestId = ++this.requestId;
            const body = new FormData();
            body.append('client', this.client.id);
            body.append('file', this.file);
            body.append('separator', this.separator);
            this.previewing = true;
            this.error = '';
            this.commitErrors = [];

            try {
                const { data } = await request(this.endpoints.preview, { method: 'POST', body });
                if (requestId !== this.requestId) return;
                this.preview = data;
                this.rows = data.rows.map((row) => ({ ...row, key: nextRowKey++ }));
                this.metaColumns = data.meta_columns.map((name) => ({ name, include: true }));
            } catch (error) {
                if (requestId !== this.requestId) return;
                this.clearPreview();
                this.error = error.message;
            } finally {
                if (requestId === this.requestId) this.previewing = false;
            }
        },
        removeRow(key) {
            this.rows = this.rows.filter((row) => row.key !== key);
        },
        reset() {
            this.requestId++;
            this.file = null;
            this.fileInputKey++;
            this.separator = 'auto';
            this.clearPreview();
        },
        async submit() {
            if (!this.canCommit) return null;

            const included = this.metaColumns.flatMap((column, index) => (column.include ? [index] : []));
            const keys = this.rows.map((row) => row.key);
            this.submitting = true;
            this.error = '';
            this.commitErrors = [];

            try {
                const { data } = await request(this.endpoints.store, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        client: this.client.id,
                        meta_columns: included.map((index) => this.metaColumns[index].name),
                        rows: this.rows.map((row) => ({
                            sampling_date: row.sampling_date,
                            project_name: row.project_name,
                            sample_name: row.sample_name,
                            sample_details: row.sample_details,
                            tht: row.tht,
                            tht_date: row.tht ? row.tht_date : null,
                            analyses_selected: row.analyses_selected,
                            misc_directions: row.misc_directions,
                            product_group: row.product_group,
                            meta: included.map((index) => row.meta[index] ?? ''),
                        })),
                    }),
                });
                this.result = data;
                this.reset();

                return data;
            } catch (error) {
                this.error = Object.keys(error.errors ?? {}).length ? 'Controleer de gemarkeerde rijen.' : error.message;
                this.commitErrors = this.describeServerErrors(error.errors ?? {}, keys);
            } finally {
                this.submitting = false;
            }

            return null;
        },
        describeServerErrors(errors, keys) {
            return Object.entries(errors).map(([field, messages]) => {
                const match = field.match(/^rows\.(\d+)\./);
                const row = match ? this.rows.find((candidate) => candidate.key === keys[Number(match[1])]) : null;

                return `${row ? `Rij ${row.line}: ` : ''}${messages[0]}`;
            });
        },
    },
});
