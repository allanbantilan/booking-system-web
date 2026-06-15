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
        class="rounded-md shadow-sm"
        :class="variant === 'dashboard'
            ? 'border-ledger-border bg-ledger-surface/70 text-ledger-text placeholder:text-slate-500 focus:border-cyan-400 focus:ring-cyan-400'
            : 'border-ledger-border focus:border-indigo-500 focus:ring-indigo-500'"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
    >
</template>
