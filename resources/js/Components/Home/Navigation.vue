<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { ref } from "vue";
import Button from "primevue/button";
import Drawer from "primevue/drawer";
import { useTheme } from "@/composables/useTheme";

withDefaults(defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    appName?: string;
    hideMainLinks?: boolean;
}>(), {
    canLogin: true,
    canRegister: true,
    appName: "BookFlow",
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
    <nav class="fixed inset-x-0 top-0 z-50 border-b border-ledger-border bg-ledger-bg/82 backdrop-blur-xl">
        <div class="mx-auto flex h-20 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
            <Link href="/" class="flex min-w-0 items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-cyan-500 text-slate-950 shadow-lg shadow-cyan-500/20"><i class="pi pi-book" /></span>
                <span class="font-display text-lg font-bold text-ledger-text">{{ appName }}</span>
            </Link>

            <div v-if="!hideMainLinks" class="ml-auto hidden items-center gap-7 md:flex">
                <button class="text-sm font-semibold text-ledger-muted hover:text-ledger-text" @click="scrollTo('features')">Features</button>
                <button class="text-sm font-semibold text-ledger-muted hover:text-ledger-text" @click="scrollTo('about')">How it works</button>
                <button class="text-sm font-semibold text-ledger-muted hover:text-ledger-text" @click="scrollTo('contact')">Get started</button>
            </div>

            <div class="ml-auto flex items-center gap-2 md:ml-4">
                <Button text rounded severity="secondary" :icon="isDark ? 'pi pi-sun' : 'pi pi-moon'" aria-label="Toggle theme" @click="toggleTheme" />
                <Link v-if="canLogin" :href="routeUrl('login')" class="hidden px-3 py-2 text-sm font-bold text-ledger-muted hover:text-ledger-text sm:block">Sign in</Link>
                <Link v-if="canRegister" :href="routeUrl('register')" class="hidden rounded-xl bg-cyan-500 px-4 py-2 text-sm font-bold text-slate-950 hover:bg-cyan-400 sm:block">Create account</Link>
                <Button v-if="!hideMainLinks" class="md:!hidden" text rounded severity="secondary" icon="pi pi-bars" aria-label="Open menu" @click="menuOpen = true" />
            </div>
        </div>
    </nav>

    <Drawer v-model:visible="menuOpen" position="right" header="Navigate">
        <nav class="space-y-2">
            <button class="block w-full rounded-xl px-4 py-3 text-left font-semibold hover:bg-ledger-elevated" @click="scrollTo('features')">Features</button>
            <button class="block w-full rounded-xl px-4 py-3 text-left font-semibold hover:bg-ledger-elevated" @click="scrollTo('about')">How it works</button>
            <button class="block w-full rounded-xl px-4 py-3 text-left font-semibold hover:bg-ledger-elevated" @click="scrollTo('contact')">Get started</button>
        </nav>
    </Drawer>
</template>
