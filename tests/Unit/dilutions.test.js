import { afterEach, beforeEach, test } from 'node:test';
import assert from 'node:assert/strict';
import { createPinia, setActivePinia } from 'pinia';
import { createDilutionDraft, dilutionKey, dilutionPayload, dilutionProblem, draftDilutionProblem, toObject } from '../../resources/js/dilutions.js';
import { useSampleLookupStore } from '../../resources/js/stores/sampleLookupStore.js';

const originalFetch = globalThis.fetch;
const response = (data, ok = true) => ({ ok, json: async () => ({ data }) });
const editor = () => ({
    analysis_id: 12, name: 'Count', uses_dilution: true, uses_replicates: true,
    dilutions: [{ value: '0.1', replicates: 1 }, { value: '0.01', replicates: 0 }],
});

test('only consecutive decades are a usable curve', () => {
    assert.equal(dilutionProblem(''), '');
    assert.equal(dilutionProblem('-1=0.1\n-2=0.01\n-3=0.001'), '');
    assert.equal(dilutionProblem('-3=0.001\n-1=0.1\n-2=0.01'), '');
    assert.equal(dilutionProblem('0=1\n-1=0.1'), '');
    assert.match(dilutionProblem('-1=0.1\n-3=0.001'), /aansluiten/);
    assert.match(dilutionProblem('0=1\n-2=0.01'), /aansluiten/);
    assert.match(dilutionProblem('-1=0.5'), /machten van tien/);
    assert.match(dilutionProblem('0=10'), /machten van tien/);
    assert.match(dilutionProblem('-1=abc'), /numerieke waarde/);
    assert.match(dilutionProblem('-1'), /numerieke waarde/);
});

test('dilution keys follow the legacy naming', () => {
    assert.equal(dilutionKey('1'), '0');
    assert.equal(dilutionKey('0.001'), '-3');
    assert.equal(dilutionKey('0.5'), '0.5');
});

test('payload keeps existing replicate counts until the replicates field is changed', () => {
    const draft = createDilutionDraft(editor());
    assert.equal(draft.replicates, 1);
    assert.deepEqual(toObject(draft.text), { '-1': '0.1', '-2': '0.01' });

    draft.text += '\n-3=0.001';
    assert.deepEqual(dilutionPayload(draft), [
        { value: '0.1', replicates: 1 }, { value: '0.01', replicates: 0 }, { value: '0.001', replicates: 1 },
    ]);

    draft.replicates = 2;
    draft.replicatesTouched = true;
    assert.deepEqual(dilutionPayload(draft).map((dilution) => dilution.replicates), [2, 2, 2]);
});

test('a draft needs at least one dilution and a sequential curve', () => {
    const draft = createDilutionDraft(editor());
    assert.equal(draftDilutionProblem(draft), '');
    draft.text = '';
    assert.match(draftDilutionProblem(draft), /minimaal één/);
    draft.text = '-1=0.1\n-3=0.001';
    assert.match(draftDilutionProblem(draft), /aansluiten/);
});

beforeEach(() => {
    setActivePinia(createPinia());
    globalThis.document = { querySelector: () => ({ content: 'csrf-token' }) };
    globalThis.window = { location: { href: 'http://localhost/laboratory/samples/lookup' }, history: { replaceState() {} } };
    const store = useSampleLookupStore();
    store.endpoints = {
        dilutions: '/samples/__SAMPLE__/analyses/__ANALYSIS__/dilutions',
        updateDilutions: '/samples/__SAMPLE__/analyses/__ANALYSIS__/dilutions',
        results: '/analyses/__ANALYSIS__/results',
    };
    store.data = { sample: { id: 5, barcode: 'sample' }, project: { locked: 0, auth_status: 0 }, analyses: [{ id: 12, profile: 1, is_ready: true }] };
    store.selectedId = 12;
});
afterEach(() => {
    globalThis.fetch = originalFetch;
    delete globalThis.document;
    delete globalThis.window;
});

test('dilutions cannot be changed without a selection or in a read-only sample', async () => {
    const store = useSampleLookupStore();
    let requests = 0;
    globalThis.fetch = async () => { requests++; return response(editor()); };
    assert.equal(store.canEditDilutions, true);
    store.data.reasons = ['authorized'];
    assert.equal(store.canEditDilutions, false);
    await store.openDilutions();
    store.data.reasons = [];
    store.selectedId = null;
    await store.openDilutions();
    assert.equal(requests, 0);
});

test('an invalid curve is not sent to the server', async () => {
    const store = useSampleLookupStore();
    globalThis.fetch = async () => response(editor());
    await store.openDilutions();
    store.dilutionDraft.text = '-1=0.1\n-3=0.001';
    let patched = false;
    globalThis.fetch = async () => { patched = true; return response({}); };

    assert.equal(await store.saveDilutions(), false);
    assert.equal(patched, false);
    assert.match(store.dilutionsError, /aansluiten/);
});

test('saving posts the dilutions, applies the refreshed sample and closes the dialog', async () => {
    const store = useSampleLookupStore();
    let requested;
    globalThis.fetch = async (url) => { requested = url; return response(editor()); };
    await store.openDilutions();
    assert.equal(requested, '/samples/5/analyses/12/dilutions');
    store.dilutionDraft.text = '-1=0.1';

    const refreshed = { sample: { id: 5, barcode: 'sample' }, project: { locked: 0, auth_status: 0 }, analyses: [{ id: 12, profile: 1, is_ready: false }] };
    let payload;
    globalThis.fetch = async (url, options) => {
        if (options?.method === 'PATCH') { payload = JSON.parse(options.body); return response(refreshed); }
        return response({ analysis: null });
    };

    assert.equal(await store.saveDilutions(), true);
    assert.deepEqual(payload, { dilutions: [{ value: '0.1', replicates: 1 }] });
    assert.equal(store.data.analyses[0].is_ready, false);
    assert.equal(store.dilutionsVisible, false);
});
