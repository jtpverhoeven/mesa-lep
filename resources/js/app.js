import { createApp } from 'vue';
import { createPinia } from 'pinia';
import ClientCategoryForm from './components/ClientCategoryForm.vue';
import ClientCategoryManager from './components/ClientCategoryManager.vue';
import ClientDirectory from './components/ClientDirectory.vue';
import ClientForm from './components/ClientForm.vue';
import CvarTable from './components/CvarTable.vue';
import MediaSelector from './components/MediaSelector.vue';
import ResearchProfileBulkEditor from './components/ResearchProfileBulkEditor.vue';
import ResearchProfileDirectory from './components/ResearchProfileDirectory.vue';
import ResearchProfileEditor from './components/ResearchProfileEditor.vue';
import SampleCreateForm from './components/SampleCreateForm.vue';
import UserMenu from './components/UserMenu.vue';

const app = createApp({});

app.use(createPinia());
app.component('client-category-form', ClientCategoryForm);
app.component('client-category-manager', ClientCategoryManager);
app.component('client-directory', ClientDirectory);
app.component('client-form', ClientForm);
app.component('cvar-table', CvarTable);
app.component('media-selector', MediaSelector);
app.component('research-profile-bulk-editor', ResearchProfileBulkEditor);
app.component('research-profile-directory', ResearchProfileDirectory);
app.component('research-profile-editor', ResearchProfileEditor);
app.component('sample-create-form', SampleCreateForm);
app.component('user-menu', UserMenu);
app.mount('#app');