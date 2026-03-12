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
        class="inline-flex items-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150"
        :class="variant === 'dashboard'
            ? 'bg-cyan-500 text-slate-950 hover:bg-cyan-400 focus:ring-cyan-400 focus:ring-offset-slate-950'
            : 'bg-gray-800 text-white hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:ring-indigo-500 focus:ring-offset-2'"
    >
        <slot />
    </button>
</template>
