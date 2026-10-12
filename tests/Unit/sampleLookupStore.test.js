import { afterEach, beforeEach, test } from 'node:test';
import assert from 'node:assert/strict';
import { createPinia, setActivePinia } from 'pinia';
import { useSampleLookupStore } from '../../resources/js/stores/sampleLookupStore.js';
import { useSampleResearchStore } from '../../resources/js/stores/sampleResearchStore.js';

const originalFetch = globalThis.fetch;
beforeEach(() => {
    setActivePinia(createPinia());
    useSampleLookupStore().endpoints.page = '/laboratory/samples/lookup';
    globalThis.window = { location: { href: 'http://localhost/laboratory/samples/lookup' }, history: { replaceState(state, title, url) { window.location.href = String(url); } } };
    globalThis.document = { querySelector: () => ({ content: 'csrf-token' }) };
});
afterEach(() => { globalThis.fetch = originalFetch; delete globalThis.window; delete globalThis.document; });

test('progress uses the predicted-date percentage and does not change when results become ready', () => {
    const store = useSampleLookupStore();
    assert.equal(store.progress, 0);
    store.data = { sample: { id: 5 }, progress: 34, analyses: [{ id: 12, is_ready: false }] };
    assert.equal(store.progress, 34);

    store.applyCalculation(12, { isReady: true });

    assert.equal(store.data.analyses[0].is_ready, true);
    assert.equal(store.progress, 34);
    store.data = { sample: { id: 5 }, progress: 100, analyses: [] };
    assert.equal(store.progress, 100);
});

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
    assert.equal(window.location.href, 'http://localhost/laboratory/samples/lookup/second');
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
    assert.equal(window.location.href, 'http://localhost/laboratory/samples/lookup/26091000.2.3');
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
    assert.equal(window.location.href, 'http://localhost/laboratory/samples/lookup/26091000.2.3');
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
    store.endpoints = { page: '/laboratory/samples/lookup', lookup: '/lookup', results: '/analyses/__ANALYSIS__/results' };
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

function detailStore() {
    const store = useSampleLookupStore();
    const details = {
        sample: { id: 5, barcode: '26091000', description: 'Water sample', sampling_method: 1 },
        sample_fields: [{ name: 'temperature', value: '4 C' }],
        sample_extra: [{ name: 'filter_volume', value: '1000' }],
        sampling_methods: [{ id: 1, name: 'Original method' }, { id: 2, name: 'Updated method' }],
        read_only: false,
    };
    store.endpoints.sampleDetails = '/lookup/projects/__PROJECT__/samples/__SAMPLE__/details';
    store.data = { ...structuredClone(details), project: { id: 7, auth_status: 0, locked: 0 }, analyses: [{ id: 12 }], project_samples: [{ id: 5, description: 'Water sample' }] };
    store.detailsData = details;
    store.detailsVisible = true;
    store.selectedId = 12;
    store.resultData = { rows: [{ id: 30 }] };
    store.calculation = { output: { result: '42' } };
    return store;
}

test('detail saves use the shared endpoint and preserve selected results and calculation', async () => {
    const store = detailStore();
    const results = store.resultData;
    const calculation = store.calculation;
    globalThis.fetch = async (url, options) => {
        assert.equal(url, '/lookup/projects/7/samples/5/details');
        assert.equal(options.method, 'PATCH');
        assert.equal(options.headers['X-CSRF-TOKEN'], 'csrf-token');
        assert.deepEqual(JSON.parse(options.body), { source: 'attribute', field: 'description', value: 'Updated sample' });
        return { ok: true, json: async () => ({ data: { sample: { description: 'Updated sample' }, read_only: false } }) };
    };

    await store.saveDetailField('attribute', 'description', 'Updated sample');

    assert.equal(store.data.sample.description, 'Updated sample');
    assert.equal(store.data.project_samples[0].description, 'Updated sample');
    assert.equal(store.detailsData.sample.description, 'Updated sample');
    assert.equal(store.selectedId, 12);
    assert.equal(store.resultData, results);
    assert.equal(store.calculation, calculation);
    assert.equal(store.detailsBusy, false);
});

test('a sampling method save refreshes its lookup label', async () => {
    const store = detailStore();
    globalThis.fetch = async () => ({ ok: true, json: async () => ({ data: { sample: { sampling_method: 2 }, sampling_methods: store.detailsData.sampling_methods, read_only: false } }) });

    await store.saveDetailField('attribute', 'sampling_method', '2');

    assert.equal(store.data.sample.sampling_method, 2);
    assert.equal(store.data.sampling_method, 'Updated method');
});

test('a rejected sampling method restores the editor ID missing from the lookup payload', async () => {
    const store = detailStore();
    delete store.data.sample.sampling_method;
    const details = { sample: { sampling_method: 1 }, sample_fields: [], sample_extra: [], read_only: false };
    globalThis.fetch = async (url, options) => options?.method === 'PATCH'
        ? { ok: false, status: 422, json: async () => ({ errors: { value: ['Selecteer een geldige monsternameprocedure.'] } }) }
        : { ok: true, json: async () => ({ data: details }) };

    await store.openDetails();
    store.detailsData.sample.sampling_method = 2;
    await store.saveDetailField('attribute', 'sampling_method', '2');

    assert.equal(store.data.sample.sampling_method, 1);
    assert.equal(store.detailsData.sample.sampling_method, 1);
});

test('concurrent custom and extra saves merge only their own field', async () => {
    const store = detailStore();
    const responses = [];
    globalThis.fetch = () => new Promise((resolve) => responses.push(resolve));
    const custom = store.saveDetailField('custom', 'temperature', '8 C');
    const extra = store.saveDetailField('extra', 'filter_volume', '500');
    responses[1]({ ok: true, json: async () => ({ data: { sample_fields: [{ name: 'temperature', value: '4 C' }], sample_extra: [{ name: 'filter_volume', value: '500' }], read_only: false } }) });
    await extra;
    responses[0]({ ok: true, json: async () => ({ data: { sample_fields: [{ name: 'temperature', value: '8 C' }], sample_extra: [{ name: 'filter_volume', value: '1000' }], read_only: false } }) });
    await custom;

    assert.equal(store.data.sample_fields[0].value, '8 C');
    assert.equal(store.data.sample_extra[0].value, '500');
    assert.equal(store.detailsData.sample_extra[0].value, '500');
});

test('failed detail saves restore the saved value and show the validation error', async () => {
    const store = detailStore();
    store.detailsData.sample.description = 'Invalid';
    globalThis.fetch = async () => ({ ok: false, status: 422, json: async () => ({ errors: { value: ['Ongeldige waarde.'] } }) });

    await store.saveDetailField('attribute', 'description', 'Invalid');

    assert.equal(store.data.sample.description, 'Water sample');
    assert.equal(store.detailsData.sample.description, 'Water sample');
    assert.equal(store.detailsError, 'Ongeldige waarde.');
    assert.equal(store.detailsBusy, false);
    assert.equal(store.readOnly, false);
});

test('a rejected detail save freezes the editor after project authorization changes', async () => {
    const store = detailStore();
    let requests = 0;
    globalThis.fetch = async () => {
        requests++;
        return { ok: false, status: 403, json: async () => ({ message: 'Dit project is vergrendeld of geautoriseerd.' }) };
    };

    await store.saveDetailField('attribute', 'description', 'Not allowed');
    await store.saveDetailField('attribute', 'description', 'Still not allowed');
    await store.openDetails();

    assert.equal(requests, 1);
    assert.equal(store.readOnly, true);
    assert.equal(store.data.sample.description, 'Water sample');
    assert.match(store.detailsError, /geautoriseerd/);
});

test('authorized and locked projects cannot open or save details', async () => {
    const store = detailStore();
    globalThis.fetch = () => { throw new Error('Frozen projects must not send detail requests.'); };
    for (const field of ['auth_status', 'locked']) {
        store.data.project = { id: 7, auth_status: 0, locked: 0, [field]: 1 };
        store.detailsVisible = false;
        await store.openDetails();
        await store.saveDetailField('attribute', 'description', 'Not allowed');
        assert.equal(store.detailsVisible, false);
        assert.equal(store.detailsBusy, false);
    }
});

test('scanning another sample ignores an in-flight detail response', async () => {
    const store = detailStore();
    let finishDetails;
    globalThis.fetch = (url) => {
        if (url.includes('/details')) return new Promise((resolve) => { finishDetails = resolve; });
        return Promise.resolve({ ok: true, json: async () => ({ data: { sample: { id: 6, barcode: '26091001' }, analyses: [] } }) });
    };

    const pending = store.openDetails();
    await store.lookup('26091001');
    finishDetails({ ok: true, json: async () => ({ data: { sample: { id: 5 } } }) });
    await pending;

    assert.equal(store.data.sample.id, 6);
    assert.equal(store.detailsData, null);
    assert.equal(store.detailsVisible, false);
    assert.equal(store.detailsLoading, false);
});

test('sample navigation waits for an in-flight detail save', async () => {
    const store = detailStore();
    let finish;
    globalThis.fetch = () => new Promise((resolve) => { finish = resolve; });
    const pending = store.saveDetailField('attribute', 'description', 'Updated sample');

    assert.equal(await store.lookup('26091001'), null);
    assert.equal(store.data.sample.id, 5);
    finish({ ok: true, json: async () => ({ data: { sample: { description: 'Updated sample' }, read_only: false } }) });
    await pending;
});