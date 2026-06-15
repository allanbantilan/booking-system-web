<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import Navigation from "@/Components/Home/Navigation.vue";
import Footer from "@/Components/Home/Footer.vue";

withDefaults(defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    appName?: string;
}>(), {
    canLogin: true,
    canRegister: true,
    appName: "BookFlow",
});

const features = [
    { icon: "pi pi-compass", title: "Discover with confidence", text: "Compare clear pricing, availability, amenities, and merchant details before you reserve." },
    { icon: "pi pi-calendar-clock", title: "Everything on schedule", text: "Track upcoming reservations, booking history, and status updates from one focused workspace." },
    { icon: "pi pi-shield", title: "Secure checkout trail", text: "Follow payment state and retrieve receipts without losing context or jumping between tools." },
];

const steps = [
    { number: "01", title: "Explore", text: "Filter available listings by category and price." },
    { number: "02", title: "Reserve", text: "Choose quantity, dates, or duration with a clear price summary." },
    { number: "03", title: "Track", text: "Manage status, receipts, and upcoming plans in your ledger." },
];

const routeUrl = (name: string) => route(name);
</script>

<template>
    <Head :title="`${appName} | Booking System`" />

    <div class="min-h-screen overflow-hidden bg-ledger-bg text-ledger-text">
        <Navigation :can-login="canLogin" :can-register="canRegister" :app-name="appName" />

        <main>
            <section class="relative px-4 pb-24 pt-40 sm:px-6 lg:px-8 lg:pb-32 lg:pt-48">
                <div class="pointer-events-none absolute inset-0 ledger-grid opacity-30" />
                <div class="pointer-events-none absolute left-[12%] top-24 size-80 rounded-full bg-cyan-500/18 blur-3xl" />
                <div class="pointer-events-none absolute right-[8%] top-32 size-72 rounded-full bg-orange-500/16 blur-3xl" />

                <div class="relative mx-auto grid max-w-7xl gap-14 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
                    <div>
                        <p class="inline-flex items-center gap-2 rounded-full border border-ledger-border bg-ledger-surface/70 px-4 py-2 text-xs font-bold uppercase tracking-[0.2em] text-cyan-400">
                            <span class="size-1.5 rounded-full bg-cyan-400 shadow-[0_0_12px_rgba(34,211,238,0.9)]" />
                            Booking, clearly accounted for
                        </p>
                        <h1 class="mt-7 max-w-4xl font-display text-5xl font-bold leading-[1.02] tracking-tight sm:text-6xl lg:text-7xl">
                            Keep every plan in your
                            <span class="text-cyan-400"> Night Ledger.</span>
                        </h1>
                        <p class="mt-6 max-w-2xl text-base leading-7 text-ledger-muted sm:text-lg">
                            Explore trusted listings, reserve in minutes, and keep schedules, payments, and receipts organized in one calm workspace.
                        </p>
                        <div class="mt-9 flex flex-wrap gap-3">
                            <Link
                                :href="routeUrl(canRegister ? 'register' : 'dashboard')"
                                class="rounded-xl bg-cyan-500 px-6 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:-translate-y-0.5 hover:bg-cyan-400"
                            >
                                {{ canRegister ? "Create your account" : "Open dashboard" }}
                                <i class="pi pi-arrow-right ml-2" />
                            </Link>
                            <Link :href="routeUrl('login')" class="rounded-xl border border-ledger-border bg-ledger-surface px-6 py-3 text-sm font-bold text-ledger-text transition hover:border-cyan-400">
                                Sign in
                            </Link>
                        </div>
                    </div>

                    <div class="relative mx-auto w-full max-w-lg">
                        <div class="ledger-panel rotate-2 rounded-3xl p-4">
                            <div class="rounded-2xl border border-ledger-border bg-ledger-elevated p-5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-ledger-muted">Upcoming</p>
                                        <p class="mt-2 font-display text-xl font-bold">Boracay beachfront stay</p>
                                    </div>
                                    <span class="grid size-11 place-items-center rounded-xl bg-emerald-500/15 text-emerald-400"><i class="pi pi-check" /></span>
                                </div>
                                <div class="mt-6 grid grid-cols-2 gap-3">
                                    <div class="rounded-xl border border-ledger-border bg-ledger-surface p-4">
                                        <p class="text-xs text-ledger-muted">Status</p>
                                        <p class="mt-1 font-bold text-emerald-400">Confirmed</p>
                                    </div>
                                    <div class="rounded-xl border border-ledger-border bg-ledger-surface p-4">
                                        <p class="text-xs text-ledger-muted">Check-in</p>
                                        <p class="mt-1 font-bold">Jun 22</p>
                                    </div>
                                </div>
                                <div class="mt-3 rounded-xl border border-ledger-border bg-ledger-surface p-4">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-ledger-muted">Payment receipt</span>
                                        <span class="font-bold text-cyan-400">Ready</span>
                                    </div>
                                    <div class="mt-3 h-1.5 rounded-full bg-ledger-border"><div class="h-full w-full rounded-full bg-cyan-400" /></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="features" class="border-y border-ledger-border bg-ledger-surface/55 px-4 py-24 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-orange-400">Built around the booking journey</p>
                    <div class="mt-4 flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
                        <h2 class="max-w-3xl font-display text-3xl font-bold sm:text-5xl">Less uncertainty between discovery and arrival.</h2>
                        <p class="max-w-md text-sm leading-6 text-ledger-muted">Every screen emphasizes the decision you need to make next, without hiding the details that matter.</p>
                    </div>
                    <div class="mt-12 grid gap-5 md:grid-cols-3">
                        <article v-for="feature in features" :key="feature.title" class="ledger-panel rounded-2xl p-6">
                            <span class="grid size-12 place-items-center rounded-xl bg-cyan-500/12 text-cyan-400"><i :class="feature.icon" /></span>
                            <h3 class="mt-6 font-display text-xl font-bold">{{ feature.title }}</h3>
                            <p class="mt-3 text-sm leading-6 text-ledger-muted">{{ feature.text }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="about" class="px-4 py-24 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <div class="grid gap-10 lg:grid-cols-[0.65fr_1.35fr]">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-cyan-400">How it works</p>
                            <h2 class="mt-4 font-display text-3xl font-bold sm:text-4xl">A short path from idea to confirmed plan.</h2>
                        </div>
                        <div class="divide-y divide-ledger-border border-y border-ledger-border">
                            <article v-for="step in steps" :key="step.number" class="grid gap-4 py-7 sm:grid-cols-[5rem_1fr_1fr] sm:items-center">
                                <span class="font-display text-2xl font-bold text-orange-400">{{ step.number }}</span>
                                <h3 class="font-display text-xl font-bold">{{ step.title }}</h3>
                                <p class="text-sm leading-6 text-ledger-muted">{{ step.text }}</p>
                            </article>
                        </div>
                    </div>
                </div>
            </section>

            <section id="contact" class="px-4 pb-24 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl overflow-hidden rounded-3xl border border-ledger-border bg-ledger-surface p-8 shadow-ledger sm:p-12">
                    <div class="grid gap-8 lg:grid-cols-[1fr_auto] lg:items-end">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-orange-400">Ready when you are</p>
                            <h2 class="mt-4 max-w-3xl font-display text-3xl font-bold sm:text-5xl">Turn your next plan into a confirmed booking.</h2>
                            <p class="mt-4 max-w-xl text-sm leading-6 text-ledger-muted">Create an account to explore the complete catalogue and keep every reservation within reach.</p>
                        </div>
                        <Link :href="routeUrl(canRegister ? 'register' : 'dashboard')" class="rounded-xl bg-cyan-500 px-6 py-3 text-center text-sm font-bold text-slate-950 transition hover:bg-cyan-400">
                            Get started <i class="pi pi-arrow-right ml-2" />
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <Footer :app-name="appName" />
    </div>
</template>
