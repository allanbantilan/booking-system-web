<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import Navbar from "@/Components/Dashboard/Navbar.vue";

const props = defineProps({
    title: {
        type: String,
        default: "",
    },
});

const page = usePage();
const appName = computed(() => page.props.appName || "BookFlow");
const pageTitle = computed(() => {
    if (!props.title) return appName.value;
    return `${props.title} | ${appName.value}`;
});
</script>

<template>
    <Head :title="pageTitle" />

    <div class="relative min-h-screen overflow-hidden bg-slate-950 text-white">
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_18%_20%,rgba(6,182,212,0.2),transparent_40%),radial-gradient(circle_at_82%_12%,rgba(249,115,22,0.2),transparent_35%),radial-gradient(circle_at_55%_100%,rgba(16,185,129,0.16),transparent_45%)]"
        />

        <Navbar :title="title" />

        <div class="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
