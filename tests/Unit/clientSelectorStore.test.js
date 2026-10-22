import { afterEach, beforeEach, test } from 'node:test';
import assert from 'node:assert/strict';
import { createPinia, setActivePinia } from 'pinia';
import { useClientSelectorStore } from '../../resources/js/stores/clientSelectorStore.js';

const originalFetch = globalThis.fetch;
beforeEach(() => {
    setActivePinia(createPinia());
    useClientSelectorStore().configure('/clients/search', '/clients/__CLIENT__/details');
});
afterEach(() => {
    useClientSelectorStore().clear();
    globalThis.fetch = originalFetch;
});

test('client details load from the configured endpoint with contact information, wishes and files', async () => {
    const details = { reference: 'TEST', address: 'Teststraat 12', telephone: '0101234567', notes: 'Koel vervoeren', files: [{ name: 'Instructie' }] };
    let requestedUrl;
    let requestOptions;
    globalThis.fetch = async (url, options) => {
        requestedUrl = url;
        requestOptions = options;
        return { ok: true, json: async () => ({ data: details }) };
    };
    const store = useClientSelectorStore();

    await store.loadDetails(12);

    assert.equal(requestedUrl, '/clients/12/details');
    assert.equal(requestOptions.headers.Accept, 'application/json');
    assert.deepEqual(store.details, details);
    assert.equal(store.loadingDetails, false);
    assert.equal(store.detailsError, '');
});

test('a late response cannot replace the newly selected client details even if abort is ignored', async () => {
    const requests = [];
    globalThis.fetch = (url, options) => new Promise((resolve) => requests.push({ resolve, signal: options.signal }));
    const store = useClientSelectorStore();
    const first = store.loadDetails(1);
    const second = store.loadDetails(2);

    requests[1].resolve({ ok: true, json: async () => ({ data: { notes: 'Second', files: [] } }) });
    await second;
    requests[0].resolve({ ok: true, json: async () => ({ data: { notes: 'First', files: [] } }) });
    await first;

    assert.equal(requests[0].signal.aborted, true);
    assert.equal(store.details.notes, 'Second');
    assert.equal(store.loadingDetails, false);
});

test('clearing the client cancels details and ignores a late response', async () => {
    let resolveResponse;
    globalThis.fetch = () => new Promise((resolve) => { resolveResponse = resolve; });
    const store = useClientSelectorStore();
    store.select({ id: 1, name: 'First' });
    const request = store.loadDetails(1);

    store.clear();
    resolveResponse({ ok: true, json: async () => ({ data: { notes: 'Stale', files: [] } }) });
    await request;

    assert.equal(store.selected, null);
    assert.equal(store.details, null);
    assert.equal(store.loadingDetails, false);
    assert.equal(store.detailsError, '');
});

test('selecting a different client immediately removes the previous details', () => {
    const store = useClientSelectorStore();
    store.details = { notes: 'Old instructions', files: [] };

    store.select({ id: 2, name: 'Second' });

    assert.equal(store.details, null);
    assert.equal(store.selected.id, 2);
});

test('disabling details clears them without fetching another client', async () => {
    let fetchCalls = 0;
    globalThis.fetch = async () => { fetchCalls++; };
    const store = useClientSelectorStore();
    store.details = { notes: 'Instructions', files: [] };

    await store.loadDetails(null);

    assert.equal(fetchCalls, 0);
    assert.equal(store.details, null);
});

test('failed requests show an error without retaining previous client details', async () => {
    globalThis.fetch = async () => ({ ok: false });
    const store = useClientSelectorStore();
    store.details = { notes: 'Previous', files: [] };

    await store.loadDetails(1);

    assert.equal(store.details, null);
    assert.equal(store.loadingDetails, false);
    assert.equal(store.detailsError, 'Klantgegevens konden niet worden opgehaald.');
});

test('an outdated failed request cannot overwrite the latest request state', async () => {
    let rejectFirst;
    globalThis.fetch = () => new Promise((resolve, reject) => { rejectFirst = reject; });
    const store = useClientSelectorStore();
    const first = store.loadDetails(1);
    globalThis.fetch = async () => ({ ok: true, json: async () => ({ data: { notes: 'Latest', files: [] } }) });
    await store.loadDetails(2);

    rejectFirst(new Error('Late failure'));
    await first;

    assert.equal(store.details.notes, 'Latest');
    assert.equal(store.detailsError, '');
});

test('an enabled selector without a details endpoint reports a configuration error', async () => {
    const store = useClientSelectorStore();
    store.configure('/clients/search');

    await store.loadDetails(1);

    assert.equal(store.detailsError, 'Het adres voor klantgegevens is niet ingesteld.');
    assert.equal(store.loadingDetails, false);
});
