<script setup lang="ts">
defineProps<{
    label: string;
    value: string | number;
    icon: string;
    tone?: "cyan" | "orange" | "emerald" | "rose";
    description?: string;
}>();

const tones = {
    cyan: "bg-cyan-500/12 text-cyan-400",
    orange: "bg-orange-500/12 text-orange-400",
    emerald: "bg-emerald-500/12 text-emerald-400",
    rose: "bg-rose-500/12 text-rose-400",
};
</script>

<template>
    <article class="ledger-panel rounded-2xl p-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-ledger-muted">{{ label }}</p>
                <p class="mt-3 font-display text-3xl font-bold text-ledger-text">{{ value }}</p>
                <p v-if="description" class="mt-1 text-xs text-ledger-muted">{{ description }}</p>
            </div>
            <span class="grid size-11 place-items-center rounded-xl" :class="tones[tone || 'cyan']">
                <i :class="icon" />
            </span>
        </div>
    </article>
</template>
