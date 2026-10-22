import { afterEach, beforeEach, test } from 'node:test';
import assert from 'node:assert/strict';
import { createPinia, setActivePinia } from 'pinia';
import { useProjectSearchStore } from '../../resources/js/stores/projectSearchStore.js';

const originalFetch = globalThis.fetch;
beforeEach(() => {
    setActivePinia(createPinia());
    useProjectSearchStore().configure({
        page: '/laboratory/projects/search',
        search: '/laboratory/projects/search/data',
        project: '/laboratory/projects/search/data/__PROJECT__',
    });
    globalThis.window = {
        location: { href: 'http://localhost/laboratory/projects/search' },
        history: { replaceState(state, title, url) { window.location.href = new URL(url, window.location.href).href; } },
    };
    globalThis.document = { querySelector: () => ({ content: 'test-token' }) };
});
afterEach(() => { globalThis.fetch = originalFetch; delete globalThis.window; delete globalThis.document; });

test('selecting a route project loads its details and keeps the page URL reloadable', async () => {
    let requestedUrl;
    globalThis.fetch = async (url) => {
        requestedUrl = url;
        return { ok: true, json: async () => ({ data: {
            url: 'http://localhost/laboratory/projects/search/11',
            project: { id: 11, project_name: 'Project water' },
            samples: [],
        } }) };
    };
    const store = useProjectSearchStore();

    assert.equal(await store.selectProject(11), true);

    assert.equal(requestedUrl, '/laboratory/projects/search/data/11');
    assert.equal(store.selectedProjectId, 11);
    assert.equal(store.projectData.project.project_name, 'Project water');
    assert.equal(window.location.href, 'http://localhost/laboratory/projects/search/11');
});

test('barcode searches select a project using the separate details endpoint', async () => {
    const requestedUrls = [];
    globalThis.fetch = async (url) => {
        requestedUrls.push(url);
        const data = url.includes('?')
            ? { projects: [], selected_project_id: 11, selected_sample_id: null }
            : { url: '/laboratory/projects/search/11', project: { id: 11 }, samples: [] };
        return { ok: true, json: async () => ({ data }) };
    };
    const store = useProjectSearchStore();
    store.query = '26091016';

    assert.equal(await store.search(), true);

    assert.deepEqual(requestedUrls, [
        '/laboratory/projects/search/data?mode=barcode&query=26091016',
        '/laboratory/projects/search/data/11',
    ]);
    assert.equal(store.selectedProjectId, 11);
    assert.equal(window.location.href, 'http://localhost/laboratory/projects/search/11');
});

test('a new search clears the previous project from the URL', async () => {
    globalThis.fetch = async () => ({ ok: true, json: async () => ({ data: { projects: [] } }) });
    const store = useProjectSearchStore();
    store.selectedProjectId = 11;
    store.mode = 'reference';
    store.query = 'missing';
    window.location.href = 'http://localhost/laboratory/projects/search/11';

    await store.search();

    assert.equal(store.selectedProjectId, null);
    assert.equal(window.location.href, 'http://localhost/laboratory/projects/search');
});

test('failed project loading reports the error without replacing the page URL', async () => {
    globalThis.fetch = async () => ({ ok: false, json: async () => ({ message: 'Project not found.' }) });
    const store = useProjectSearchStore();

    assert.equal(await store.selectProject(999), false);

    assert.equal(store.selectedProjectId, null);
    assert.equal(store.error, 'Project not found.');
    assert.equal(window.location.href, 'http://localhost/laboratory/projects/search');
});

function vetoStore() {
    const store = useProjectSearchStore();
    store.endpoints.veto = '/projects/__PROJECT__/samples/__SAMPLE__/analyses/__ANALYSIS__/veto';
    store.endpoints.results = '/projects/__PROJECT__/samples/__SAMPLE__/analyses/__ANALYSIS__/results';
    store.selectedProjectId = 11;
    store.selectedSampleId = 22;
    store.projectData = { project: { id: 11, auth_status: 0, locked: 0 }, samples: [{ id: 22 }] };
    store.sampleData = { analyses: [{ id: 33, conf_requested: 0, is_ready: false }] };
    return store;
}

for (const remove of [false, true]) {
    test(`${remove ? 'removing' : 'saving'} a veto updates the effective result and project without changing sample selection`, async () => {
        const store = vetoStore();
        const calls = [];
        globalThis.fetch = async (url, options) => {
            calls.push({ url, options });
            return { ok: true, json: async () => ({ data: url.endsWith('/veto') ? {
                calculation: { output: { kve: remove ? 'Aangetoond' : '0' }, isReady: true },
                analysis: { id: 33, is_ready: true, conf_requested: 2 },
                veto_results: remove ? [] : [{ parameter: 'kve', result: '0', reason: 'Controle' }],
            } : store.projectData }) };
        };

        assert.equal(await store.changeVeto(33, { parameter: 'kve', value: '0', reason: 'Controle' }, remove), true);

        assert.equal(calls[0].url, '/projects/11/samples/22/analyses/33/veto');
        assert.equal(calls[0].options.method, remove ? 'DELETE' : 'PUT');
        assert.equal(calls[0].options.headers['X-CSRF-TOKEN'], 'test-token');
        assert.equal(JSON.parse(calls[0].options.body).reason, 'Controle');
        assert.equal(store.analysisResults[33].data.calculation.output.kve, remove ? 'Aangetoond' : '0');
        assert.equal(store.sampleData.analyses[0].is_ready, true);
        assert.equal(store.sampleData.analyses[0].conf_requested, 2);
        assert.equal(store.selectedSampleId, 22);
        assert.equal(store.savingVeto, false);
    });
}

test('a rejected veto keeps the previous result and exposes the validation message', async () => {
    const store = vetoStore();
    store.analysisResults[33] = { data: { calculation: { output: { kve: 'Oud' } } }, error: '' };
    globalThis.fetch = async () => ({ ok: false, json: async () => ({ message: 'Een reden is verplicht.' }) });

    assert.equal(await store.changeVeto(33, { parameter: 'kve', reason: '' }), false);

    assert.equal(store.analysisResults[33].data.calculation.output.kve, 'Oud');
    assert.equal(store.vetoError, 'Een reden is verplicht.');
    assert.equal(store.savingVeto, false);
});

test('veto writes are single-flight and responses cannot overwrite a newly selected sample', async () => {
    const store = vetoStore();
    let finish;
    let calls = 0;
    globalThis.fetch = () => {
        calls++;
        return new Promise((resolve) => { finish = resolve; });
    };
    const pending = store.changeVeto(33, { parameter: 'kve', value: '0', reason: 'Controle' });
    assert.equal(store.savingVeto, true);
    assert.equal(await store.changeVeto(33, {}), false);
    store.selectedSampleId = 44;
    store.sampleData = { analyses: [{ id: 55 }] };
    store.analysisResults = {};
    finish({ ok: true, json: async () => ({ data: { analysis: { id: 33 }, calculation: { output: { kve: '0' } } } }) });

    assert.equal(await pending, true);

    assert.equal(calls, 1);
    assert.deepEqual(store.analysisResults, {});
    assert.deepEqual(store.sampleData.analyses, [{ id: 55 }]);
    assert.equal(store.savingVeto, false);
});

test('authorized or locked projects do not submit veto writes', async () => {
    const store = vetoStore();
    globalThis.fetch = async () => assert.fail('No request should be made');
    store.projectData.project.auth_status = 1;
    assert.equal(await store.changeVeto(33, {}), false);
    store.projectData.project.auth_status = 0;
    store.projectData.project.locked = 1;
    assert.equal(await store.changeVeto(33, {}, true), false);
});

test('realtime refreshes retrieve veto metadata and older result requests cannot overwrite them', async () => {
    const store = vetoStore();
    let finishOld;
    globalThis.fetch = () => new Promise((resolve) => { finishOld = resolve; });
    const pending = store.refreshAnalysisResult(33);
    globalThis.fetch = async () => ({ ok: true, json: async () => ({ data: {
        calculation: { output: { kve: '0' } },
        veto_results: [{ parameter: 'kve', result: '0', reason: 'Controle' }],
        analysis: { id: 33, is_ready: true },
    } }) });

    await store.refreshAnalysisResult(33);
    finishOld({ ok: true, json: async () => ({ data: { calculation: { output: { kve: 'Oud' } } } }) });
    await pending;

    assert.equal(store.analysisResults[33].data.calculation.output.kve, '0');
    assert.equal(store.analysisResults[33].data.veto_results[0].reason, 'Controle');
    assert.equal(store.sampleData.analyses[0].is_ready, true);
});

test('calculation failures stay visible and ignore broadcasts for another sample', async () => {
    const store = vetoStore();
    let finish;
    globalThis.fetch = () => new Promise((resolve) => { finish = resolve; });
    const pending = store.refreshAnalysisResult(33);
    store.applyCalculationFailure({ sample_id: 22, analysis_id: 33, message: 'Berekening mislukt' });
    finish({ ok: true, json: async () => ({ data: { calculation: null, calculation_queued: true } }) });
    await pending;
    store.applyCalculationFailure({ sample_id: 99, analysis_id: 33, message: 'Ander monster' });

    assert.equal(store.analysisResults[33].error, 'Berekening mislukt');
});

test('result requests cannot update a newly selected sample', async () => {
    const store = vetoStore();
    let finish;
    globalThis.fetch = () => new Promise((resolve) => { finish = resolve; });
    const pending = store.refreshAnalysisResult(33);
    store.selectedSampleId = 44;
    store.analysisResults = {};
    finish({ ok: true, json: async () => ({ data: { analysis: { id: 33 }, calculation: { output: { kve: '0' } } } }) });
    await pending;

    assert.deepEqual(store.analysisResults, {});
});

test('unavailable live updates explicitly flag queued results but leave completed results intact', () => {
    const store = vetoStore();
    store.analysisResults = {
        33: { data: { calculation_queued: true }, error: '' },
        34: { data: { calculation: { output: { kve: '0' } }, calculation_queued: false }, error: '' },
    };

    store.reportRealtimeError();

    assert.equal(store.analysisResults[33].error, 'Live updates zijn niet beschikbaar. Selecteer het monster opnieuw.');
    assert.equal(store.analysisResults[34].error, '');
});