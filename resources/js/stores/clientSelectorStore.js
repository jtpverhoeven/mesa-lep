import { defineStore } from 'pinia';

let activeRequest;
let detailsRequest;

export const useClientSelectorStore = defineStore('sampleClientSelector', {
    state: () => ({
        endpoint: '', detailsEndpoint: '', query: '', results: [], selected: null, loading: false, error: '',
        details: null, loadingDetails: false, detailsError: '',
    }),
    actions: {
        configure(endpoint, detailsEndpoint = '') {
            this.endpoint = endpoint;
            this.detailsEndpoint = detailsEndpoint;
        },
        clearDetails() {
            detailsRequest?.abort();
            detailsRequest = null;
            this.details = null;
            this.loadingDetails = false;
            this.detailsError = '';
        },
        async loadDetails(clientId) {
            this.clearDetails();
            if (!clientId) return;

            if (!this.detailsEndpoint) {
                this.detailsError = 'Het adres voor klantgegevens is niet ingesteld.';
                return;
            }

            const request = new AbortController();
            detailsRequest = request;
            this.loadingDetails = true;

            try {
                const response = await fetch(this.detailsEndpoint.replace('__CLIENT__', clientId), {
                    headers: { Accept: 'application/json' },
                    signal: request.signal,
                });
                if (!response.ok) throw new Error();
                const data = await response.json();
                if (detailsRequest === request) this.details = data.data;
            } catch (error) {
                if (detailsRequest === request && error.name !== 'AbortError') {
                    this.detailsError = 'Klantgegevens konden niet worden opgehaald.';
                }
            } finally {
                if (detailsRequest === request) this.loadingDetails = false;
            }
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
            this.clearDetails();
            this.selected = client;
            this.query = client.name;
            this.results = [];
        },
        clear() {
            activeRequest?.abort();
            this.clearDetails();
            this.query = '';
            this.results = [];
            this.selected = null;
            this.loading = false;
            this.error = '';
        },
    },
});