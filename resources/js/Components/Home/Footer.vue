<script setup lang="ts">
import { Link } from "@inertiajs/vue3";

withDefaults(defineProps<{ appName?: string }>(), { appName: "BookFlow" });
const currentYear = new Date().getFullYear();
const routeUrl = (name: string) => route(name);
</script>

<template>
    <footer class="border-t border-ledger-border bg-ledger-surface/60 px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="grid size-9 place-items-center rounded-lg bg-cyan-500 text-slate-950"><i class="pi pi-bolt" /></span>
                <div>
                    <p class="font-display font-bold text-ledger-text">{{ appName }}</p>
                    <p class="text-xs text-ledger-muted">Booking, clearly accounted for.</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-5 text-sm font-semibold text-ledger-muted">
                <Link :href="routeUrl('login')" class="hover:text-ledger-text">Sign in</Link>
                <Link :href="routeUrl('register')" class="hover:text-ledger-text">Create account</Link>
                <Link :href="routeUrl('policy.show')" class="hover:text-ledger-text">Privacy</Link>
                <Link :href="routeUrl('terms.show')" class="hover:text-ledger-text">Terms</Link>
            </div>
            <p class="text-xs text-ledger-muted">&copy; {{ currentYear }} {{ appName }}</p>
        </div>
    </footer>
</template>
