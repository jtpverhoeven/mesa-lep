import { afterEach, beforeEach, test } from 'node:test';
import assert from 'node:assert/strict';
import { createPinia, setActivePinia } from 'pinia';
import { useSampleLookupStore } from '../../resources/js/stores/sampleLookupStore.js';

const originalFetch = globalThis.fetch;
const response = (data) => ({ ok: true, json: async () => ({ data }) });
const editor = () => ({
    analysis_id: 12, name: 'Count', reference: { ref_kve: '10', ref_old: '20' }, reference_source: 7,
    configuration: { reference_outputs: [{ key: 'ref_kve', name: 'kve', label: 'kve' }, { key: 'ref_ph', name: 'ph', label: 'pH' }] },
    reference_sources: [{ id: 7, name: 'Global' }],
});

beforeEach(() => {
    setActivePinia(createPinia());
    globalThis.document = { querySelector: () => ({ content: 'csrf-token' }) };
    globalThis.window = { location: { href: 'http://localhost/laboratory/samples/lookup' }, history: { replaceState() {} } };
    const store = useSampleLookupStore();
    store.endpoints = {
        page: '/laboratory/samples/lookup', lookup: '/lookup',
        references: '/samples/__SAMPLE__/analyses/__ANALYSIS__/references',
        updateReferences: '/samples/__SAMPLE__/analyses/__ANALYSIS__/references',
        results: '/analyses/__ANALYSIS__/results',
    };
    store.data = { sample: { id: 5, barcode: 'sample' }, project: { locked: 0, auth_status: 0 }, analyses: [{ id: 12, profile: 0, is_ready: true }] };
    store.selectedId = 12;
});
afterEach(() => {
    globalThis.fetch = originalFetch;
    delete globalThis.document;
    delete globalThis.window;
});

test('reference editing is restricted to selected roaming analyses in editable projects', async () => {
    const store = useSampleLookupStore();
    let requests = 0;
    globalThis.fetch = async () => { requests++; return response(editor()); };
    assert.equal(store.canEditReferences, true);
    for (const field of ['locked', 'auth_status']) {
        store.data.project[field] = 1;
        assert.equal(store.canEditReferences, false);
        await store.openReferences();
        store.data.project[field] = 0;
    }
    store.selected.profile = 3;
    assert.equal(store.canEditReferences, false);
    await store.openReferences();
    store.selectedId = null;
    assert.equal(store.canEditReferences, false);
    await store.openReferences();
    assert.equal(requests, 0);
});

test('opening uses server settings and initializes all outputs without losing legacy keys', async () => {
    const store = useSampleLookupStore();
    let requested;
    const data = editor();
    globalThis.fetch = async (url) => { requested = url; return response(data); };

    await store.openReferences();
    store.referenceDraft.reference.ref_kve = '99';

    assert.equal(requested, '/samples/5/analyses/12/references');
    assert.deepEqual(store.referenceDraft.reference, { ref_kve: '99', ref_old: '20', ref_ph: '' });
    assert.equal(data.reference.ref_kve, '10');
    assert.equal(store.referencesVisible, true);
    assert.equal(store.referencesLoading, false);
    assert.equal(store.referenceProblem, '');
});

test('editing an imported maz7218 reference sends the original unprefixed key without a second reference', async () => {
    const store = useSampleLookupStore();
    globalThis.fetch = async () => response({
        ...editor(), reference: { kve: '100' },
        configuration: { reference_outputs: [{ key: 'ref_kve', name: 'kve', label: 'kve' }] },
    });
    await store.openReferences();
    assert.deepEqual(store.referenceDraft.reference, { kve: '100' });
    store.referenceDraft.reference.kve = '200';
    let payload;
    globalThis.fetch = async (url, options) => {
        if (options?.method === 'PATCH') {
            payload = JSON.parse(options.body);
            return response(store.data);
        }
        return response({ rows: [], calculation: null, calculation_queued: true });
    };

    assert.equal(await store.saveReferences(), true);
    assert.deepEqual(payload, { reference: { kve: '200' }, reference_source: 7 });
});

test('zero requires a source and prevents a request until one is chosen', async () => {
    const store = useSampleLookupStore();
    globalThis.fetch = async () => response(editor());
    await store.openReferences();
    store.referenceDraft.reference = { ref_kve: '0' };
    store.referenceDraft.reference_source = null;
    globalThis.fetch = () => assert.fail('Invalid references must not be sent.');

    assert.equal(await store.saveReferences(), false);
    assert.match(store.referenceProblem, /referentiebron is verplicht/);
    assert.equal(store.referencesError, store.referenceProblem);
    assert.equal(store.referencesVisible, true);
    assert.equal(store.saving, false);
});

test('saving sends only reference values and source and refreshes result references and calculation', async () => {
    const store = useSampleLookupStore();
    globalThis.fetch = async () => response(editor());
    await store.openReferences();
    store.referenceDraft.reference = { ref_kve: '0', ref_ph: '', ref_old: '20' };
    const requests = [];
    globalThis.fetch = async (url, options) => {
        requests.push({ url, options });
        if (options?.method === 'PATCH') return response({ ...store.data, analyses: [{ id: 12, profile: 0, settings: { reference: { ref_kve: '0', ref_old: '20' }, reference_source: 7 } }] });
        return response({ rows: [], reference: '0', calculation: { output: { kve: '2' }, isReady: true } });
    };

    assert.equal(await store.saveReferences(), true);

    assert.equal(requests[0].url, '/samples/5/analyses/12/references');
    assert.deepEqual(JSON.parse(requests[0].options.body), { reference: { ref_kve: '0', ref_old: '20' }, reference_source: 7 });
    assert.equal(requests[0].options.headers['X-CSRF-TOKEN'], 'csrf-token');
    assert.equal(requests[1].url, '/analyses/12/results');
    assert.equal(store.resultData.reference, '0');
    assert.equal(store.calculation.output.kve, '2');
    assert.equal(store.referencesVisible, false);
    assert.equal(store.referenceDraft, null);
    assert.equal(store.saving, false);
});

test('delete sends an empty reference map and no source even if the current source is unavailable', async () => {
    const store = useSampleLookupStore();
    globalThis.fetch = async () => response({ ...editor(), reference_sources: [] });
    await store.openReferences();
    assert.match(store.referenceProblem, /niet beschikbaar voor dit monster/);
    let payload;
    globalThis.fetch = async (url, options) => {
        if (options?.method === 'PATCH') {
            payload = JSON.parse(options.body);
            return response(store.data);
        }
        return response({ rows: [], reference: null, calculation: null, calculation_queued: true });
    };

    assert.equal(await store.saveReferences(true), true);
    assert.deepEqual(payload, { reference: {}, reference_source: null });
    assert.equal(store.resultData.reference, null);
});

test('server validation failures preserve the draft and old calculation for correction and retry', async () => {
    const store = useSampleLookupStore();
    globalThis.fetch = async () => response(editor());
    await store.openReferences();
    store.calculation = { output: { kve: 'old' } };
    globalThis.fetch = async () => ({ ok: false, json: async () => ({ errors: { reference_source: ['Bron is niet meer beschikbaar.'] } }) });

    assert.equal(await store.saveReferences(), false);
    assert.equal(store.referencesError, 'Bron is niet meer beschikbaar.');
    assert.equal(store.referenceDraft.reference.ref_kve, '10');
    assert.equal(store.calculation.output.kve, 'old');
    assert.equal(store.referencesVisible, true);
    assert.equal(store.saving, false);
});

test('failed editor loads show an error without allowing writes', async () => {
    const store = useSampleLookupStore();
    globalThis.fetch = async () => ({ ok: false, json: async () => ({ message: 'Niet beschikbaar.' }) });

    await store.openReferences();

    assert.equal(store.referencesError, 'Niet beschikbaar.');
    assert.equal(store.referenceDraft, null);
    assert.equal(store.referencesLoading, false);
    assert.equal(await store.saveReferences(true), false);
});

test('closing or navigating invalidates pending reference editor responses', async () => {
    const store = useSampleLookupStore();
    let finish;
    globalThis.fetch = () => new Promise((resolve) => { finish = resolve; });
    const pending = store.openReferences();
    store.closeReferences();
    finish(response(editor()));
    await pending;
    assert.equal(store.referenceDraft, null);
    assert.equal(store.referencesVisible, false);

    const nextPending = store.openReferences();
    globalThis.fetch = async () => response({ sample: { id: 6, barcode: 'next' }, analyses: [] });
    await store.lookup('next');
    finish(response(editor()));
    await nextPending;
    assert.equal(store.data.sample.id, 6);
    assert.equal(store.referenceDraft, null);
    assert.equal(store.referencesVisible, false);
});

test('the latest editor request wins and a changed selection cannot save the old draft', async () => {
    const store = useSampleLookupStore();
    const finishes = [];
    globalThis.fetch = () => new Promise((resolve) => finishes.push(resolve));
    const first = store.openReferences();
    const second = store.openReferences();
    finishes[1](response({ ...editor(), reference: { ref_kve: 'new' } }));
    await second;
    finishes[0](response(editor()));
    await first;
    assert.equal(store.referenceDraft.reference.ref_kve, 'new');
    store.data.analyses.push({ id: 13, profile: 0 });
    store.selectedId = 13;
    globalThis.fetch = () => assert.fail('A stale draft must not be saved.');
    assert.equal(await store.saveReferences(), false);
});

test('saving is single-flight and blocks closing and barcode navigation until the write settles', async () => {
    const store = useSampleLookupStore();
    globalThis.fetch = async () => response(editor());
    await store.openReferences();
    let finish;
    globalThis.fetch = () => new Promise((resolve) => { finish = resolve; });
    const saving = store.saveReferences();
    assert.equal(store.saving, true);
    assert.equal(await store.saveReferences(), false);
    assert.equal(await store.lookup('another'), null);
    store.closeReferences();
    assert.equal(store.referencesVisible, true);
    finish({ ok: false, json: async () => ({ message: 'Try again' }) });
    await saving;
    assert.equal(store.saving, false);
    assert.equal(store.referencesVisible, true);
});
