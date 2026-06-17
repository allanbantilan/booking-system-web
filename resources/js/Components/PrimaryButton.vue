<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
    variant: {
        type: String,
        default: 'light',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
        :class="variant === 'dashboard'
            ? 'border border-ledger-text bg-ledger-text text-ledger-bg hover:bg-ledger-muted focus:ring-ledger-text focus:ring-offset-ledger-surface'
            : 'border border-ledger-text bg-ledger-text text-ledger-bg hover:bg-ledger-muted focus:ring-ledger-text focus:ring-offset-ledger-surface'"
    >
        <slot />
    </button>
</template>
