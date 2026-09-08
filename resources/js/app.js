import { createApp } from 'vue';
import ClientCategoryManager from './components/ClientCategoryManager.vue';
import ClientDirectory from './components/ClientDirectory.vue';
import ClientForm from './components/ClientForm.vue';
import CvarTable from './components/CvarTable.vue';
import MediaSelector from './components/MediaSelector.vue';
import UserMenu from './components/UserMenu.vue';

document.querySelectorAll('[data-user-menu]').forEach((element) => {
    createApp(UserMenu, JSON.parse(element.dataset.userMenu)).mount(element);
});

document.querySelectorAll('[data-media-selector]').forEach((element) => {
    createApp(MediaSelector, JSON.parse(element.dataset.mediaSelector)).mount(element);
});

document.querySelectorAll('[data-cvar-table]').forEach((element) => {
    createApp(CvarTable, JSON.parse(element.dataset.cvarTable)).mount(element);
});

document.querySelectorAll('[data-client-directory]').forEach((element) => {
    createApp(ClientDirectory, JSON.parse(element.dataset.clientDirectory)).mount(element);
});

document.querySelectorAll('[data-client-category-manager]').forEach((element) => {
    createApp(ClientCategoryManager, JSON.parse(element.dataset.clientCategoryManager)).mount(element);
});

document.querySelectorAll('[data-client-form]').forEach((element) => {
    createApp(ClientForm, JSON.parse(element.dataset.clientForm)).mount(element);
});