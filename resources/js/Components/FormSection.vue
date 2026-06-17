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
        return 'border border-ledger-border bg-ledger-surface text-ledger-text';
    }

    return 'bg-ledger-surface';
});

const actionsClasses = computed(() => {
    if (props.variant === 'dashboard') {
        return 'border-t border-ledger-border bg-ledger-elevated text-ledger-text';
    }

    return 'bg-gray-50';
});
</script>

<template>
    <section
        :class="variant === 'dashboard'
            ? 'rounded-xl border border-ledger-border bg-ledger-surface'
            : 'md:grid md:grid-cols-3 md:gap-6'"
    >
        <SectionTitle :variant="variant">
            <template #title>
                <slot name="title" />
            </template>
            <template #description>
                <slot name="description" />
            </template>
        </SectionTitle>

        <div :class="variant === 'dashboard' ? '' : 'mt-5 md:mt-0 md:col-span-2'">
            <form @submit.prevent="$emit('submitted')">
                <div
                    :class="[
                        variant === 'dashboard' ? 'px-4 pb-5 sm:px-6 sm:pb-6' : 'px-4 py-5 shadow sm:p-6',
                        panelClasses,
                        variant === 'dashboard' ? 'border-0' : hasActions
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
                    :class="[
                        variant === 'dashboard'
                            ? 'flex items-center justify-end rounded-b-xl px-4 py-4 text-end sm:px-6'
                            : 'flex items-center justify-end px-4 py-3 text-end shadow sm:rounded-bl-md sm:rounded-br-md sm:px-6',
                        actionsClasses,
                    ]"
                >
                    <slot name="actions" />
                </div>
            </form>
        </div>
    </section>
</template>
