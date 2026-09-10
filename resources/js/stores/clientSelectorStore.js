import { defineStore } from 'pinia';

let activeRequest;

export const useClientSelectorStore = defineStore('sampleClientSelector', {
    state: () => ({ endpoint: '', query: '', results: [], selected: null, loading: false, error: '' }),
    actions: {
        configure(endpoint) {
            this.endpoint = endpoint;
        },
        async search(query) {
            this.query = query;
            this.error = '';

            if (query.trim().length < 2) {
                activeRequest?.abort();
                this.results = [];
                this.loading = false;
                return;
            }

            activeRequest?.abort();
            const request = new AbortController();
            activeRequest = request;
            this.loading = true;

            try {
                const url = new URL(this.endpoint, window.location.origin);
                url.searchParams.set('query', query.trim());
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    signal: request.signal,
                });
                if (!response.ok) throw new Error();
                this.results = (await response.json()).data;
            } catch (error) {
                if (error.name !== 'AbortError') {
                    this.results = [];
                    this.error = 'Klanten konden niet worden opgehaald.';
                }
            } finally {
                if (activeRequest === request) this.loading = false;
            }
        },
        select(client) {
            this.selected = client;
            this.query = client.name;
            this.results = [];
        },
        clear() {
            activeRequest?.abort();
            this.query = '';
            this.results = [];
            this.selected = null;
            this.loading = false;
            this.error = '';
        },
    },
});