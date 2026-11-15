import { defineStore } from 'pinia';
import { getEcho } from '../echo.js';

let channelJoined = false;

export const useSidebarCountsStore = defineStore('sidebarCounts', {
    state: () => ({
        counts: {},
        notInoculatedVisible: false,
    }),
    actions: {
        hydrate(counts) {
            this.counts = { ...(counts ?? {}) };
        },
        subscribe(echo = getEcho) {
            if (channelJoined) return;

            try {
                echo().private('sidebar-counts').listen('.sidebar.counts.updated', (event) => this.hydrate(event.counts));
                channelJoined = true;
            } catch {
                // The counts rendered with the page stay valid until the next navigation.
            }
        },
    },
});
