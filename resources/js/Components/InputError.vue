<script setup>
defineProps({
    message: String,
    variant: {
        type: String,
        default: 'light',
    },
});
</script>

<template>
    <div v-show="message">
        <p
            class="text-sm"
            :class="variant === 'dashboard' ? 'text-rose-300' : 'text-red-600'"
        >
            {{ message }}
        </p>
    </div>
</template>
