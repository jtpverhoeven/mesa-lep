import { createApp } from 'vue';
import MediaSelector from './components/MediaSelector.vue';
import UserMenu from './components/UserMenu.vue';

document.querySelectorAll('[data-user-menu]').forEach((element) => {
    createApp(UserMenu, JSON.parse(element.dataset.userMenu)).mount(element);
});

document.querySelectorAll('[data-media-selector]').forEach((element) => {
    createApp(MediaSelector, JSON.parse(element.dataset.mediaSelector)).mount(element);
});