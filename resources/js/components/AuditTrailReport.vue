<script setup>
import { ref } from 'vue';
import { FlaskConical, FolderOpen } from '@lucide/vue';
import Tab from 'openvue/tab';
import TabList from 'openvue/tablist';
import TabPanel from 'openvue/tabpanel';
import TabPanels from 'openvue/tabpanels';
import Tabs from 'openvue/tabs';
import AuditTrailProject from './AuditTrailProject.vue';
import AuditTrailSample from './AuditTrailSample.vue';

defineProps({ trail: { type: Object, required: true }, links: { type: Object, required: true } });
const subject = ref('sample');
</script>

<template>
    <Tabs v-model:value="subject" class="audit-subjects">
        <TabList>
            <Tab value="sample"><FlaskConical :size="15" />Monster {{ trail.barcode }}</Tab>
            <Tab v-if="trail.project" value="project"><FolderOpen :size="15" />Project {{ trail.project.overview.reference || `#${trail.project.id}` }}</Tab>
        </TabList>
        <TabPanels>
            <TabPanel value="sample"><AuditTrailSample :sample="trail.sample" :links="links" /></TabPanel>
            <TabPanel v-if="trail.project" value="project"><AuditTrailProject :project="trail.project" :links="links" /></TabPanel>
        </TabPanels>
    </Tabs>
</template>
