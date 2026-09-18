<script setup>
import { Barcode, Search, Tag } from '@lucide/vue';
import Tab from 'openvue/tab';
import TabList from 'openvue/tablist';
import TabPanel from 'openvue/tabpanel';
import TabPanels from 'openvue/tabpanels';
import Tabs from 'openvue/tabs';
import { sfx } from '../sfx.js';
import { useProjectSearchStore } from '../stores/projectSearchStore';

const store = useProjectSearchStore();

async function search(mode) {
    const isBarcodeScan = mode === 'barcode';
    const loaded = await store.search();

    if (isBarcodeScan && loaded !== null) void sfx.play(loaded ? 'do' : 'warning').catch(() => {});
}
</script>

<template>
    <section class="sample-panel project-search-panel">
        <h2><Search :size="16" />Project zoeken</h2>
        <Tabs :value="store.mode" @update:value="store.setMode">
            <TabList>
                <Tab value="barcode"><Barcode :size="15" />Barcode</Tab>
                <Tab value="reference"><Tag :size="15" />Referentie</Tab>
            </TabList>
            <TabPanels>
                <TabPanel v-for="mode in ['barcode', 'reference']" :key="mode" :value="mode">
                    <form class="project-search-form" @submit.prevent="search(mode)">
                        <label class="sr-only" :for="`project-search-${mode}`">{{ mode === 'barcode' ? 'Barcode' : 'Referentie' }}</label>
                        <div class="project-search-control">
                            <Barcode v-if="mode === 'barcode'" :size="16" aria-hidden="true" />
                            <Tag v-else :size="16" aria-hidden="true" />
                            <input :id="`project-search-${mode}`" v-model="store.query" :autofocus="mode === 'barcode'" autocomplete="off" :placeholder="mode === 'barcode' ? 'Scan een barcode' : 'Project- of klantreferentie'" @focus="$event.target.select()">
                        </div>
                        <button class="button primary" :disabled="store.searching || !store.query.trim()">
                            <Search :size="17" /><span>Zoeken</span>
                        </button>
                    </form>
                </TabPanel>
            </TabPanels>
        </Tabs>
    </section>
</template>