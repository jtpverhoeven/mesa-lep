<script setup>
import { computed, reactive, ref } from 'vue';
import { Ellipsis, FileText, Network } from '@lucide/vue';
import Tab from 'openvue/tab';
import TabList from 'openvue/tablist';
import TabPanel from 'openvue/tabpanel';
import TabPanels from 'openvue/tabpanels';
import Tabs from 'openvue/tabs';
import SenderAddressing from './SenderAddressing.vue';
import SenderFilesTable from './SenderFilesTable.vue';
import SenderMessageBlock from './SenderMessageBlock.vue';
import SenderSubmit from './SenderSubmit.vue';

const props = defineProps({ config: { type: Object, required: true } });
const groups = props.config.contactGroups ?? [];
const addressing = reactive({
    receivers: groups.filter((group) => group.checked).map((group) => group.id),
    extra: '',
    cc: '',
    bcc: props.config.bcc ?? '',
});
const messageFiles = ref(props.config.template.template ?? '');
const activeTab = ref('addressing');
const selectedHashes = ref([]);
const returnToProject = ref(true);
const payload = computed(() => ({
    receivers: addressing.receivers,
    extraReceiver: addressing.extra,
    cc: addressing.cc,
    bcc: addressing.bcc,
    messageFiles: messageFiles.value,
    includeSampleFiles: selectedHashes.value,
}));
const validationError = computed(() => (selectedHashes.value.length ? '' : 'U dient minstens 1 bijlage te selecteren die u wilt verzenden'));
</script>

<template>
    <div class="grid gap-3">
        <section class="sample-panel" aria-labelledby="send-files">
            <h2 id="send-files">Verstuur monster bestanden naar klant</h2>
            <div class="sample-panel-body">
                <dl class="report-summary">
                    <div><dt>Project</dt><dd>{{ config.project.reference }}</dd></div>
                </dl>
            </div>
            <Tabs v-model:value="activeTab" class="sender-tabs">
                <TabPanels>
                    <TabPanel value="addressing">
                        <SenderAddressing v-model:receivers="addressing.receivers" v-model:extra="addressing.extra" v-model:cc="addressing.cc" v-model:bcc="addressing.bcc" :groups="groups" :error="config.contactGroupsError" />
                    </TabPanel>
                    <TabPanel value="message">
                        <SenderMessageBlock v-model="messageFiles" caption="Email voor losse bestanden." :selector="config.template" :template-url="config.urls.template" @bcc="addressing.bcc = $event" />
                    </TabPanel>
                </TabPanels>
                <TabList>
                    <Tab value="addressing"><Network :size="14" aria-hidden="true" />Adressering</Tab>
                    <Tab value="message"><FileText :size="14" aria-hidden="true" />Bericht</Tab>
                </TabList>
            </Tabs>
        </section>

        <section class="sample-panel" aria-labelledby="send-files-table">
            <h2 id="send-files-table">Bestanden te versturen in email</h2>
            <SenderFilesTable v-model="selectedHashes" :files="config.files" :history-url="config.urls.history" label="Bestanden te versturen in email" />
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <SenderSubmit :url="config.urls.mail" label="Verzenden" :payload="payload" :has-receivers="addressing.receivers.length > 0 || addressing.extra.trim() !== ''" :validation-error="validationError" :redirect-url="returnToProject ? config.urls.project : ''" />
            <label class="flex items-center gap-2"><input v-model="returnToProject" type="checkbox"> Terug naar rapport na verzenden</label>
            <a class="button" :href="config.urls.reportForm">Terug naar rapport uitvoeren</a>
        </div>
    </div>
</template>
