<script setup>
import { computed, useSlots } from 'vue';
import SectionTitle from './SectionTitle.vue';

defineEmits(['submitted']);

const props = defineProps({
    variant: {
        type: String,
        default: 'light',
    },
});

const hasActions = computed(() => !! useSlots().actions);

const panelClasses = computed(() => {
    if (props.variant === 'dashboard') {
        return 'bg-slate-900/60 border border-white/10 text-slate-100';
    }

    return 'bg-white';
});

const actionsClasses = computed(() => {
    if (props.variant === 'dashboard') {
        return 'bg-slate-900/70 border border-white/10 text-slate-200';
    }

    return 'bg-gray-50';
});
</script>

<template>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <SectionTitle :variant="variant">
            <template #title>
                <slot name="title" />
            </template>
            <template #description>
                <slot name="description" />
            </template>
        </SectionTitle>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <form @submit.prevent="$emit('submitted')">
                <div
                    class="px-4 py-5 sm:p-6 shadow"
                    :class="[
                        panelClasses,
                        hasActions
                            ? 'sm:rounded-tl-md sm:rounded-tr-md'
                            : 'sm:rounded-md',
                    ]"
                >
                    <div class="grid grid-cols-6 gap-6">
                        <slot name="form" />
                    </div>
                </div>

                <div
                    v-if="hasActions"
                    class="flex items-center justify-end px-4 py-3 text-end sm:px-6 shadow sm:rounded-bl-md sm:rounded-br-md"
                    :class="actionsClasses"
                >
                    <slot name="actions" />
                </div>
            </form>
        </div>
    </div>
</template>
