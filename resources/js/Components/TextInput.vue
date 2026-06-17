<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: String,
    variant: {
        type: String,
        default: 'light',
    },
});

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        ref="input"
        class="rounded-lg shadow-sm"
        :class="variant === 'dashboard'
            ? 'border-ledger-border bg-ledger-surface text-ledger-text placeholder:text-ledger-muted focus:border-ledger-text focus:ring-ledger-text'
            : 'border-ledger-border focus:border-indigo-500 focus:ring-indigo-500'"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
    >
</template>
