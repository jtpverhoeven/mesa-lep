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
});
afterEach(() => { globalThis.fetch = originalFetch; delete globalThis.window; });

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