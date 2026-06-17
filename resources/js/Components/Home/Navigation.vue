<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { ref } from "vue";
import Button from "primevue/button";
import Drawer from "primevue/drawer";
import BrandMark from "@/Components/BrandMark.vue";
import { useTheme } from "@/composables/useTheme";

withDefaults(defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    appName?: string;
    hideMainLinks?: boolean;
}>(), {
    canLogin: true,
    canRegister: true,
    appName: "BookBound",
    hideMainLinks: false,
});

const menuOpen = ref(false);
const { isDark, toggleTheme } = useTheme();
const routeUrl = (name: string) => route(name);
const scrollTo = (id: string) => {
    document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
    menuOpen.value = false;
};
</script>

<template>
    <nav class="bf-nav-enter fixed inset-x-0 top-0 z-50 border-b border-ledger-border bg-ledger-bg/95">
        <div
            class="mx-auto grid h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8"
            :class="hideMainLinks ? 'grid-cols-[1fr_auto]' : 'grid-cols-[1fr_auto] md:grid-cols-[1fr_auto_1fr]'"
        >
            <Link href="/" class="flex min-w-0 items-center gap-3 justify-self-start">
                <BrandMark class="size-9 shrink-0 text-ledger-text" />
                <span class="font-display text-lg font-bold text-ledger-text">{{ appName }}</span>
            </Link>

            <div v-if="!hideMainLinks" class="hidden items-center gap-1 rounded-lg border border-ledger-border bg-ledger-surface p-1 md:col-start-2 md:flex">
                <button class="rounded-md px-3 py-2 text-sm font-semibold text-ledger-muted hover:bg-ledger-elevated hover:text-ledger-text" @click="scrollTo('features')">Features</button>
                <button class="rounded-md px-3 py-2 text-sm font-semibold text-ledger-muted hover:bg-ledger-elevated hover:text-ledger-text" @click="scrollTo('about')">How it works</button>
                <button class="rounded-md px-3 py-2 text-sm font-semibold text-ledger-muted hover:bg-ledger-elevated hover:text-ledger-text" @click="scrollTo('contact')">Get started</button>
            </div>

            <div
                class="flex items-center gap-2 justify-self-end"
                :class="hideMainLinks ? '' : 'md:col-start-3'"
            >
                <Button text rounded severity="secondary" :icon="isDark ? 'pi pi-sun' : 'pi pi-moon'" aria-label="Toggle theme" @click="toggleTheme" />
                <Link v-if="canLogin" :href="routeUrl('login')" class="hidden px-3 py-2 text-sm font-bold text-ledger-muted hover:text-ledger-text sm:block">Sign in</Link>
                <Link v-if="canRegister" :href="routeUrl('register')" class="bf-button bf-button-primary hidden sm:inline-flex">Create account</Link>
                <Button v-if="!hideMainLinks" class="md:!hidden" text rounded severity="secondary" icon="pi pi-bars" aria-label="Open menu" @click="menuOpen = true" />
            </div>
        </div>
    </nav>

    <Drawer v-model:visible="menuOpen" position="right" header="Navigate">
        <nav class="space-y-2">
            <button class="block w-full rounded-lg px-4 py-3 text-left font-semibold hover:bg-ledger-elevated" @click="scrollTo('features')">Features</button>
            <button class="block w-full rounded-lg px-4 py-3 text-left font-semibold hover:bg-ledger-elevated" @click="scrollTo('about')">How it works</button>
            <button class="block w-full rounded-lg px-4 py-3 text-left font-semibold hover:bg-ledger-elevated" @click="scrollTo('contact')">Get started</button>
        </nav>
    </Drawer>
</template>
