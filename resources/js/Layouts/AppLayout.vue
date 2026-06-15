<script setup lang="ts">
import DashboardLayout from "@/Layouts/DashboardLayout.vue";

withDefaults(defineProps<{ title?: string }>(), { title: "" });
</script>

<template>
    <DashboardLayout :title="title">
        <div v-if="$slots.header" class="mb-6 ledger-panel rounded-2xl p-5">
            <slot name="header" />
        </div>
        <slot />
    </DashboardLayout>
</template>
