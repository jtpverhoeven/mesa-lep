import { afterEach, beforeEach, test } from 'node:test';
import assert from 'node:assert/strict';
import { createPinia, setActivePinia } from 'pinia';
import { GATHER_STEPS, useAuditTrailStore } from '../../resources/js/stores/auditTrailStore.js';

const originalFetch = globalThis.fetch;
beforeEach(() => {
    setActivePinia(createPinia());
    globalThis.window = { location: { href: 'http://localhost/laboratory/audit-trail' }, history: { replaceState(state, title, url) { window.location.href = String(url); } } };
    const store = useAuditTrailStore();
    Object.assign(store, { endpoints: { page: '/laboratory/audit-trail', data: '/laboratory/audit-trail/data', print: '/laboratory/audit-trail/__BARCODE__/print' }, stepDelay: 1, finishDelay: 0 });
});
afterEach(() => { globalThis.fetch = originalFetch; delete globalThis.window; });

test('a scanned barcode loads the audit trail, finishes the gathering animation and updates the url', async () => {
    const store = useAuditTrailStore();
    let requested;
    globalThis.fetch = async (url) => { requested = String(url); return { ok: true, json: async () => ({ data: { barcode: '26091000', sample: {}, project: { id: 1 } } }) }; };

    assert.equal(await store.lookup(' 26091000.2 '), true);

    assert.equal(requested, '/laboratory/audit-trail/data?barcode=26091000.2');
    assert.equal(store.loading, false);
    assert.equal(store.step, GATHER_STEPS.length);
    assert.equal(store.progress, 100);
    assert.equal(store.hasProject, true);
    assert.equal(window.location.href, 'http://localhost/laboratory/audit-trail/26091000');
    assert.equal(store.printUrl(), '/laboratory/audit-trail/26091000/print');
});

test('an unknown barcode shows the legacy error and clears earlier data', async () => {
    const store = useAuditTrailStore();
    store.data = { barcode: '1' };
    globalThis.fetch = async () => ({ ok: false, status: 404, json: async () => ({}) });

    assert.equal(await store.lookup('nope'), false);

    assert.equal(store.error, 'Foute barcode');
    assert.equal(store.data, null);
    assert.equal(store.loading, false);
});

test('a slower earlier response cannot overwrite a newer scan', async () => {
    const store = useAuditTrailStore();
    const responses = [];
    globalThis.fetch = (url) => new Promise((resolve) => responses.push({ url: String(url), resolve }));

    const first = store.lookup('111');
    const second = store.lookup('222');
    responses[1].resolve({ ok: true, json: async () => ({ data: { barcode: '222', project: null } }) });
    assert.equal(await second, true);
    responses[0].resolve({ ok: true, json: async () => ({ data: { barcode: '111', project: null } }) });

    assert.equal(await first, null);
    assert.equal(store.data.barcode, '222');
    assert.equal(store.hasProject, false);
});

test('an empty scan does nothing', async () => {
    const store = useAuditTrailStore();
    globalThis.fetch = async () => { throw new Error('must not be called'); };

    assert.equal(await store.lookup('   '), false);
    assert.equal(store.loading, false);
});
