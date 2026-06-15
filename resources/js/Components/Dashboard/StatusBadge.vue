<script setup lang="ts">
import Tag from "primevue/tag";
import { computed } from "vue";

const props = defineProps<{ status: string }>();
const severity = computed(() => ({
    confirmed: "success",
    pending: "warn",
    cancelled: "danger",
}[props.status] || "secondary"));
const label = computed(() => props.status.charAt(0).toUpperCase() + props.status.slice(1));
</script>

<template>
    <Tag :value="label" :severity="severity" rounded />
</template>
