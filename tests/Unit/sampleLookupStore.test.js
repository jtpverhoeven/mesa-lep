import { afterEach, beforeEach, test } from 'node:test';
import assert from 'node:assert/strict';
import { createPinia, setActivePinia } from 'pinia';
import { useSampleLookupStore } from '../../resources/js/stores/sampleLookupStore.js';
import { useSampleResearchStore } from '../../resources/js/stores/sampleResearchStore.js';

const originalFetch = globalThis.fetch;
beforeEach(() => {
    setActivePinia(createPinia());
    globalThis.window = { location: { href: 'http://localhost/laboratory/samples/lookup' }, history: { replaceState() {} } };
});
afterEach(() => { globalThis.fetch = originalFetch; delete globalThis.window; });

test('latest barcode wins when responses arrive out of order', async () => {
    const responses = [];
    globalThis.fetch = () => new Promise((resolve) => responses.push(resolve));
    const store = useSampleLookupStore();
    store.endpoints.lookup = '/lookup';
    const first = store.lookup('first');
    const second = store.lookup('second');
    responses[1]({ ok: true, json: async () => ({ data: { sample: { id: 2, barcode: 'second' }, analyses: [] } }) });
    await second;
    responses[0]({ ok: true, json: async () => ({ data: { sample: { id: 1, barcode: 'first' }, analyses: [] } }) });
    await first;
    assert.equal(store.data.sample.id, 2);
    assert.equal(store.loading, false);
});

test('compound barcode selects the analysis and retains the plate follow number', async () => {
    let requestedUrl;
    globalThis.fetch = async (url) => {
        requestedUrl = url;
        return { ok: true, json: async () => ({ data: { sample: { id: 1, barcode: '26091000' }, analyses: [
            { id: 10, follow_number: 1 },
            { id: 20, follow_number: 2 },
        ] } }) };
    };
    const store = useSampleLookupStore();
    store.endpoints.lookup = '/lookup';

    await store.lookup('26091000.2.3');

    assert.equal(requestedUrl, '/lookup?barcode=26091000');
    assert.equal(store.selectedId, 20);
    assert.equal(store.scannedPlateFollowNumber, '3');
    assert.equal(store.barcode, '26091000.2.3');
});

test('rescanning the same sample replaces the analysis and plate targets', async () => {
    let requests = 0;
    const store = useSampleLookupStore();
    store.endpoints.lookup = '/lookup';

    globalThis.fetch = async () => {
        requests++;
        return { ok: true, json: async () => ({ data: { sample: { id: 1, barcode: '26091000' }, analyses: [
            { id: 10, follow_number: 1 },
            { id: 20, follow_number: 2 },
        ] } }) };
    };
    await store.lookup('26091000.1.1');
    const sampleData = store.data;
    store.scannedPlateFollowNumber = null;
    await store.lookup('26091000.2.3');

    assert.equal(requests, 1);
    assert.equal(store.data, sampleData);
    assert.equal(store.selectedId, 20);
    assert.equal(store.scannedPlateFollowNumber, '3');
});

test('scanning another plate in the selected analysis preserves loaded results', async () => {
    globalThis.fetch = () => { throw new Error('Same-sample plate scans must not request data.'); };
    const store = useSampleLookupStore();
    store.data = { sample: { id: 1, barcode: '26091000' }, analyses: [{ id: 20, follow_number: 2 }] };
    store.selectedId = 20;
    store.resultData = { rows: [{ id: 30, follow_no: 3 }] };

    await store.lookup('26091000.2.3');

    assert.equal(store.selectedId, 20);
    assert.equal(store.resultData.rows[0].id, 30);
    assert.equal(store.scannedPlateFollowNumber, '3');
    assert.equal(store.plateFocusRevision, 1);
});

test('failed new scan clears the old sample and ignores an old success', async () => {
    const responses = [];
    globalThis.fetch = () => new Promise((resolve) => responses.push(resolve));
    const store = useSampleLookupStore();
    const first = store.lookup('first');
    const second = store.lookup('missing');
    responses[1]({ ok: false, status: 404 });
    await second;
    responses[0]({ ok: true, json: async () => ({ data: { sample: { barcode: 'first' }, analyses: [] } }) });
    await first;
    assert.equal(store.data, null);
    assert.match(store.error, /Geen monster/);
});

test('research options from a previous sample cannot repopulate a reset selector', async () => {
    let finish;
    globalThis.fetch = () => new Promise((resolve) => { finish = resolve; });
    const store = useSampleResearchStore();
    const pending = store.load(1);
    store.reset();
    finish({ ok: true, json: async () => ({ data: { profiles: [{ id: 1 }], assays: [], matrices: [], reference_sources: [] } }) });
    await pending;
    assert.deepEqual(store.profiles, []);
    assert.equal(store.clientId, null);
    assert.equal(store.loading, false);
});

test('result loading displays a calculation-unavailable message', async () => {
    globalThis.fetch = async () => ({
        ok: true,
        json: async () => ({ data: {
            rows: [],
            calculation: null,
            calculation_queued: false,
            calculation_unavailable: 'Er is geen rekenmodule ingesteld voor deze analyse.',
        } }),
    });
    const store = useSampleLookupStore();
    store.endpoints.results = '/analyses/__ANALYSIS__/results';
    store.data = { sample: { id: 5 }, analyses: [{ id: 12 }] };
    store.selectedId = 12;

    await store.loadResults();

    assert.equal(store.calculationLoading, false);
    assert.equal(store.calculationError, 'Er is geen rekenmodule ingesteld voor deze analyse.');
});

test('a new scan invalidates an in-flight result response from the previous sample', async () => {
    let finishResults;
    globalThis.fetch = (url) => {
        if (url === '/analyses/12/results') return new Promise((resolve) => { finishResults = resolve; });

        return Promise.resolve({ ok: true, json: async () => ({ data: { sample: { id: 6, barcode: '26091001' }, analyses: [] } }) });
    };
    const store = useSampleLookupStore();
    store.endpoints = { lookup: '/lookup', results: '/analyses/__ANALYSIS__/results' };
    store.data = { sample: { id: 5 }, analyses: [{ id: 12 }] };
    store.selectedId = 12;

    const pendingResults = store.loadResults();
    await store.lookup('26091001');
    finishResults({ ok: true, json: async () => ({ data: { rows: [], fields: [], calculation: { output: { result: 'old' } } } }) });
    await pendingResults;

    assert.equal(store.data.sample.id, 6);
    assert.equal(store.resultData, null);
    assert.equal(store.calculation, null);
});

test('a calculation event wins over an in-flight queued result response', async () => {
    let finish;
    globalThis.fetch = () => new Promise((resolve) => { finish = resolve; });
    const store = useSampleLookupStore();
    store.endpoints.results = '/analyses/__ANALYSIS__/results';
    store.data = { sample: { id: 5 }, analyses: [{ id: 12, is_ready: false }] };
    store.selectedId = 12;

    const pending = store.loadResults();
    store.applyCalculationEvent({
        sample_id: 5,
        analysis_id: 12,
        calculation: { output: { result: '42' }, isReady: true },
    });
    finish({ ok: true, json: async () => ({ data: { rows: [], fields: [], calculation: null, calculation_queued: true } }) });
    await pending;

    assert.equal(store.calculation.output.result, '42');
    assert.equal(store.calculationLoading, false);
    assert.equal(store.data.analyses[0].is_ready, true);
});