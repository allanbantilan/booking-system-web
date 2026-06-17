<script setup>
defineProps({
    variant: {
        type: String,
        default: 'light',
    },
});
</script>

<template>
    <div
        class="flex justify-between"
        :class="variant === 'dashboard' ? 'px-4 py-5 sm:px-6' : 'md:col-span-1'"
    >
        <div :class="variant === 'dashboard' ? '' : 'px-4 sm:px-0'">
            <h3
                class="text-lg font-semibold"
                :class="variant === 'dashboard' ? 'text-ledger-text' : 'text-ledger-text'"
            >
                <slot name="title" />
            </h3>

            <p
                class="mt-1 text-sm"
                :class="variant === 'dashboard' ? 'text-ledger-muted' : 'text-ledger-muted'"
            >
                <slot name="description" />
            </p>
        </div>

        <div :class="variant === 'dashboard' ? '' : 'px-4 sm:px-0'">
            <slot name="aside" />
        </div>
    </div>
</template>
