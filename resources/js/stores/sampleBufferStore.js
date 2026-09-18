import { defineStore } from 'pinia';

async function request(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            ...options.headers,
        },
    });
    const data = await response.json();
    if (!response.ok) {
        const validation = Object.values(data.errors ?? {}).flat().join(' ');
        throw new Error(validation || data.message || 'De bewerking kon niet worden uitgevoerd.');
    }

    return data;
}

export const useSampleBufferStore = defineStore('sampleBuffer', {
    state: () => ({
        endpoints: {},
        tab: 'normal',
        rows: [],
        counts: {},
        samplingMethods: [],
        selected: [],
        search: '',
        sort: 'sampling_date',
        direction: 'asc',
        loading: false,
        mutating: false,
        error: '',
        requestId: 0,
    }),
    getters: {
        selectedRows: (state) => state.rows.filter((row) => state.selected.includes(row.id)),
    },
    actions: {
        configure(endpoints, initialTab) {
            this.endpoints = endpoints;
            this.tab = initialTab;
            if (initialTab === 'staged_tht') this.sort = 'tht_date';
        },
        async load() {
            const requestId = ++this.requestId;
            this.loading = true;
            this.error = '';
            const params = new URLSearchParams({ tab: this.tab, sort: this.sort, direction: this.direction, search: this.search });

            try {
                const data = await request(`${this.endpoints.data}?${params}`);
                if (requestId !== this.requestId) return;
                this.rows = data.data;
                this.counts = data.counts;
                this.samplingMethods = data.sampling_methods;
                this.selected = this.selected.filter((id) => this.rows.some((row) => row.id === id));
            } catch (error) {
                if (requestId === this.requestId) this.error = error.message;
            } finally {
                if (requestId === this.requestId) this.loading = false;
            }
        },
        setTab(tab) {
            this.tab = tab;
            this.selected = [];
            this.sort = tab === 'staged_tht' ? 'tht_date' : 'sampling_date';
            this.load();
        },
        toggle(id) {
            this.selected = this.selected.includes(id) ? this.selected.filter((value) => value !== id) : [...this.selected, id];
        },
        selectAll() {
            this.selected = this.selected.length === this.rows.length ? [] : this.rows.map((row) => row.id);
        },
        expandProjects() {
            const projects = new Set(this.selectedRows.map((row) => row.project));
            this.selected = this.rows.filter((row) => projects.has(row.project)).map((row) => row.id);
        },
        async execute(action, values = {}, ids = this.selected) {
            this.mutating = true;
            this.error = '';
            try {
                const data = await request(this.endpoints.batch, {
                    method: 'POST',
                    body: JSON.stringify({ ids, action, tab: this.tab, ...values }),
                });
                this.selected = [];
                await this.load();
                return data;
            } catch (error) {
                this.error = error.message;
                return null;
            } finally {
                this.mutating = false;
            }
        },
    },
});