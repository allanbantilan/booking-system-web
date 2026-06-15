<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
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
        class="inline-flex items-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
        :class="variant === 'dashboard'
            ? 'bg-ledger-surface/70 border border-ledger-border text-ledger-text hover:bg-ledger-elevated focus:ring-cyan-400 focus:ring-offset-slate-950'
            : 'bg-ledger-surface border border-ledger-border text-ledger-muted hover:bg-gray-50 focus:ring-indigo-500 focus:ring-offset-2'"
    >
        <slot />
    </button>
</template>
