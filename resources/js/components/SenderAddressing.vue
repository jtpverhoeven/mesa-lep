<script setup>
import { ref, useId } from 'vue';
import { Users } from '@lucide/vue';
import Button from 'openvue/button';
import InputText from 'openvue/inputtext';
import Popover from 'openvue/popover';

const props = defineProps({
    groups: { type: Array, required: true },
    error: { type: String, default: null },
    receivers: { type: Array, required: true },
    extra: { type: String, default: '' },
    cc: { type: String, default: '' },
    bcc: { type: String, default: '' },
});
const emit = defineEmits(['update:receivers', 'update:extra', 'update:cc', 'update:bcc']);
const uid = useId();
const popover = ref(null);
const peekedGroup = ref(null);

function peek(event, group) {
    if (peekedGroup.value?.id === group.id && popover.value?.visible) {
        popover.value.hide();
        return;
    }
    peekedGroup.value = group;
    popover.value.show(event);
}

function toggle(id, checked) {
    const selected = new Set(props.receivers);
    if (checked) selected.add(id); else selected.delete(id);
    emit('update:receivers', props.groups.map((group) => group.id).filter((groupId) => selected.has(groupId)));
}
</script>

<template>
    <div class="grid gap-4">
        <fieldset class="grid gap-2">
            <legend class="font-semibold">Contactgroepen</legend>
            <p v-if="error" class="report-feedback danger" role="alert">{{ error }}</p>
            <p v-else-if="!groups.length" class="report-feedback" role="status">Er zijn geen contactgroepen gevonden voor deze klant.</p>
            <div v-for="group in groups" :key="group.id" class="flex flex-wrap items-center gap-2">
                <label class="flex items-start gap-2">
                    <input type="checkbox" :checked="receivers.includes(group.id)" @change="toggle(group.id, $event.target.checked)">
                    <span>{{ group.name }}</span>
                </label>
                <Button type="button" size="small" severity="secondary" variant="outlined" :aria-label="`Ontvangers van ${group.name}`" @click="peek($event, group)"><Users :size="13" aria-hidden="true" />Ontvangers</Button>
            </div>
        </fieldset>
        <div class="field">
            <label :for="`${uid}-extra`">Extra geadresseerde</label>
            <InputText :id="`${uid}-extra`" :model-value="extra" placeholder="Indien email niet in contact groep" autocomplete="off" fluid @update:model-value="emit('update:extra', $event ?? '')" />
        </div>
        <div class="field">
            <label :for="`${uid}-cc`">CC</label>
            <InputText :id="`${uid}-cc`" :model-value="cc" autocomplete="off" fluid @update:model-value="emit('update:cc', $event ?? '')" />
        </div>
        <div class="field">
            <label :for="`${uid}-bcc`">BCC</label>
            <InputText :id="`${uid}-bcc`" :model-value="bcc" autocomplete="off" fluid @update:model-value="emit('update:bcc', $event ?? '')" />
        </div>
        <Popover ref="popover">
            <div v-if="peekedGroup" class="grid max-w-80 gap-2">
                <strong>Ontvangers</strong>
                <ul v-if="peekedGroup.members.length" class="grid gap-1 text-sm">
                    <li v-for="member in peekedGroup.members" :key="member.email" class="wrap-anywhere">{{ member.name }} &lt;{{ member.email }}&gt;</li>
                </ul>
                <p v-else class="text-sm text-muted">Geen adressen gevonden</p>
            </div>
        </Popover>
    </div>
</template>
