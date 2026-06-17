<script setup lang="ts">
defineProps<{
    label: string;
    value: string | number;
    icon: string;
    tone?: "cyan" | "orange" | "emerald" | "rose";
    description?: string;
}>();

const tones = {
    cyan: "bg-ledger-bg text-ledger-primary",
    orange: "bg-ledger-bg text-orange-400",
    emerald: "bg-ledger-bg text-emerald-400",
    rose: "bg-ledger-bg text-rose-400",
};
</script>

<template>
    <article class="ledger-panel p-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold text-ledger-muted">{{ label }}</p>
                <p class="mt-3 font-display text-3xl font-bold text-ledger-text">{{ value }}</p>
                <p v-if="description" class="mt-1 text-xs text-ledger-muted">{{ description }}</p>
            </div>
            <span class="grid size-10 place-items-center rounded-lg border border-ledger-border" :class="tones[tone || 'cyan']">
                <i :class="icon" />
            </span>
        </div>
    </article>
</template>
