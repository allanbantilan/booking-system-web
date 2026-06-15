<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import Navigation from "@/Components/Home/Navigation.vue";

defineProps<{ policy: string }>();
const routeUrl = (name: string) => route(name);
</script>

<template>
    <Head title="Privacy Policy" />
    <div class="min-h-screen bg-ledger-bg text-ledger-text">
        <Navigation :can-login="true" :can-register="true" hide-main-links />
        <main class="mx-auto max-w-4xl px-4 pb-20 pt-32 sm:px-6">
            <Link :href="routeUrl('home')" class="text-sm font-bold text-cyan-400"><i class="pi pi-arrow-left mr-2" />Back home</Link>
            <article class="prose prose-slate mt-6 max-w-none rounded-3xl border border-ledger-border bg-ledger-surface p-6 text-ledger-muted shadow-ledger dark:prose-invert sm:p-10" v-html="policy" />
        </main>
    </div>
</template>
