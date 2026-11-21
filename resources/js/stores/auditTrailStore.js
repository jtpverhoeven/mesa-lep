import { defineStore } from 'pinia';

export const GATHER_STEPS = [
    'Monster opzoeken',
    'Klant- en projectgegevens',
    'Borgingsformulier van de inzetdag',
    'Analyses, media en materiaal',
    'Bevestigingen en resultaten',
    'Revisies',
    'Projectgeschiedenis',
];

const sleep = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));

export const useAuditTrailStore = defineStore('auditTrail', {
    state: () => ({
        endpoints: {}, barcode: '', data: null, loading: false, error: '', requestId: 0,
        step: 0, stepDelay: 420, finishDelay: 90,
    }),
    getters: {
        progress: (state) => Math.round((state.step / GATHER_STEPS.length) * 100),
        hasProject: (state) => Boolean(state.data?.project),
    },
    actions: {
        // The animation advances on a timer while the request runs; once the data is in, the remaining steps finish quickly.
        async lookup(barcode = this.barcode) {
            const scanned = String(barcode ?? '').trim();
            this.barcode = scanned;
            if (!scanned) return false;

            const requestId = ++this.requestId;
            this.data = null;
            this.error = '';
            this.loading = true;
            this.step = 0;
            const ticker = setInterval(() => {
                if (this.step < GATHER_STEPS.length - 1) this.step++;
            }, Math.max(this.stepDelay, 1));

            try {
                const response = await fetch(`${this.endpoints.data}?${new URLSearchParams({ barcode: scanned })}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error(response.status === 404 ? 'Foute barcode' : 'De audit trail kon niet worden geladen.');
                const { data } = await response.json();
                clearInterval(ticker);
                while (requestId === this.requestId && this.step < GATHER_STEPS.length) {
                    this.step++;
                    await sleep(this.finishDelay);
                }
                if (requestId !== this.requestId) return null;
                this.data = data;
                this.replaceUrl(data.barcode);

                return true;
            } catch (error) {
                if (requestId !== this.requestId) return null;
                this.error = error.message;

                return false;
            } finally {
                clearInterval(ticker);
                if (requestId === this.requestId) this.loading = false;
            }
        },
        replaceUrl(barcode) {
            const url = new URL(window.location.href);
            url.pathname = `${new URL(this.endpoints.page, url).pathname}/${encodeURIComponent(barcode)}`;
            window.history.replaceState({}, '', url);
        },
        printUrl() {
            return this.data ? this.endpoints.print.replace('__BARCODE__', encodeURIComponent(this.data.barcode)) : '';
        },
    },
});
