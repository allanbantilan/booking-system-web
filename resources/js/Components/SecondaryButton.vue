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
            ? 'bg-slate-900/70 border border-white/10 text-slate-200 hover:bg-white/10 focus:ring-cyan-400 focus:ring-offset-slate-950'
            : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-indigo-500 focus:ring-offset-2'"
    >
        <slot />
    </button>
</template>
