<script setup>
defineProps({
    value: String,
    variant: {
        type: String,
        default: 'light',
    },
});
</script>

<template>
    <label
        class="block font-medium text-sm"
        :class="variant === 'dashboard' ? 'text-slate-200' : 'text-gray-700'"
    >
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
