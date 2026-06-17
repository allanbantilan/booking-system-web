<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { onBeforeUnmount, onMounted } from "vue";
import Navigation from "@/Components/Home/Navigation.vue";
import Footer from "@/Components/Home/Footer.vue";

withDefaults(defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    appName?: string;
}>(), {
    canLogin: true,
    canRegister: true,
    appName: "BookBound",
});

const features = [
    { label: "Find", title: "See what is available", text: "Browse listings with photos, price, location, and availability in one place." },
    { label: "Book", title: "Reserve the slot you want", text: "Choose quantity or dates, review the total, then continue to PayMaya." },
    { label: "Track", title: "Keep proof after payment", text: "Check booking status, receipts, and cancellation updates from your account." },
];

const steps = [
    { number: "01", title: "Pick a booking", text: "Search by category, location, or price." },
    { number: "02", title: "Confirm details", text: "Set the date, duration, or number of seats." },
    { number: "03", title: "Pay and save receipt", text: "Pay through PayMaya and keep the receipt in your history." },
];

const heroImageUrl = "https://images.unsplash.com/photo-1759038086832-795644825e3a?auto=format&fit=crop&fm=jpg&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTh8fHJlemVwdGlvbiUyMGRlcyUyMGhvdGVsc3xlbnwwfHwwfHx8MA%3D%3D&ixlib=rb-4.1.0&q=80&w=1600";
const routeUrl = (name: string) => route(name);
let motionObserver: IntersectionObserver | null = null;

onMounted(() => {
    const targets = document.querySelectorAll(".bf-motion");
    motionObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add("is-visible");
            motionObserver?.unobserve(entry.target);
        });
    }, { threshold: 0.18 });

    targets.forEach((target) => motionObserver?.observe(target));
});

onBeforeUnmount(() => motionObserver?.disconnect());
</script>

<template>
    <Head :title="`${appName} | Booking System`" />

    <div class="min-h-screen overflow-hidden bg-ledger-bg text-ledger-text">
        <Navigation :can-login="canLogin" :can-register="canRegister" :app-name="appName" />

        <main>
            <section class="relative min-h-[82svh] overflow-hidden px-4 pb-16 pt-28 text-white sm:px-6 lg:px-8 lg:pt-32">
                <img
                    :src="heroImageUrl"
                    alt="Modern hotel reception with lounge seating and a front desk ready for guest arrivals."
                    class="bf-hero-image absolute inset-0 h-full w-full object-cover"
                    loading="eager"
                    fetchpriority="high"
                />
                <div class="absolute inset-0 bg-black/55"></div>
                <div class="relative mx-auto flex min-h-[calc(82svh-9rem)] max-w-7xl items-center justify-center text-center">
                    <div class="bf-hero-copy max-w-3xl pb-6">
                        <p class="bf-motion bf-hero-kicker text-sm font-semibold text-white/75">BookBound</p>
                        <h1 class="bf-motion bf-hero-title mx-auto mt-5 max-w-3xl font-display text-[clamp(2.75rem,5vw,4.75rem)] font-bold leading-[1.02] tracking-tight text-white">
                            Bookings without loose ends.
                        </h1>
                        <p class="bf-motion bf-hero-text mx-auto mt-6 max-w-xl text-base leading-7 text-white/82 sm:text-lg">
                            Browse inventory, reserve with PayMaya, and keep receipts, schedules, and status changes in one place.
                        </p>
                        <div class="bf-motion bf-hero-actions mt-8 flex flex-wrap justify-center gap-3">
                            <Link
                                :href="routeUrl(canRegister ? 'register' : 'dashboard')"
                                class="inline-flex min-h-10 items-center justify-center rounded-lg border border-white bg-white px-4 py-2 text-sm font-bold text-black transition hover:bg-white/90"
                            >
                                {{ canRegister ? "Create your account" : "Open dashboard" }}
                                <i class="pi pi-arrow-right ml-2" />
                            </Link>
                            <Link
                                :href="routeUrl('login')"
                                class="inline-flex min-h-10 items-center justify-center rounded-lg border border-white/45 bg-black/20 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/10"
                            >
                                Sign in
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <section id="features" class="border-y border-ledger-border bg-ledger-surface px-4 py-20 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <div class="grid gap-8 lg:grid-cols-[0.7fr_1.3fr]">
                        <div class="bf-motion bf-reveal-left">
                            <h2 class="font-display text-3xl font-bold sm:text-5xl">Find, book, and track in one account.</h2>
                            <p class="mt-4 max-w-md text-sm leading-6 text-ledger-muted">BookBound keeps the full reservation flow simple from search to receipt.</p>
                        </div>
                        <div class="bf-motion bf-reveal-right divide-y divide-ledger-border border-y border-ledger-border">
                            <article
                                v-for="(feature, index) in features"
                                :key="feature.title"
                                class="bf-motion bf-reveal-item grid gap-4 py-6 sm:grid-cols-[7rem_1fr]"
                                :style="{ animationDelay: `${index * 110}ms` }"
                            >
                                <span class="text-sm font-bold text-ledger-primary">{{ feature.label }}</span>
                                <div>
                                    <h3 class="font-display text-xl font-bold">{{ feature.title }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-ledger-muted">{{ feature.text }}</p>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </section>

            <section id="about" class="px-4 py-24 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <div class="grid gap-10 lg:grid-cols-[0.65fr_1.35fr]">
                        <div class="bf-motion bf-reveal-left">
                            <h2 class="font-display text-3xl font-bold sm:text-4xl">How booking works.</h2>
                        </div>
                        <div class="bf-motion bf-reveal-right divide-y divide-ledger-border border-y border-ledger-border">
                            <article
                                v-for="(step, index) in steps"
                                :key="step.number"
                                class="bf-motion bf-reveal-item grid gap-4 py-7 sm:grid-cols-[4rem_1fr_1fr] sm:items-center"
                                :style="{ animationDelay: `${index * 110}ms` }"
                            >
                                <span class="font-display text-lg font-bold text-ledger-primary">{{ step.number }}</span>
                                <h3 class="font-display text-xl font-bold">{{ step.title }}</h3>
                                <p class="text-sm leading-6 text-ledger-muted">{{ step.text }}</p>
                            </article>
                        </div>
                    </div>
                </div>
            </section>

            <section id="contact" class="px-4 pb-24 sm:px-6 lg:px-8">
                <div class="bf-motion bf-reveal-up mx-auto max-w-7xl border border-ledger-border bg-ledger-surface p-8 sm:p-12">
                    <div class="grid gap-8 lg:grid-cols-[1fr_auto] lg:items-end">
                        <div>
                            <h2 class="max-w-3xl font-display text-3xl font-bold sm:text-5xl">Turn the next plan into a confirmed booking.</h2>
                            <p class="mt-4 max-w-xl text-sm leading-6 text-ledger-muted">Create an account to explore the complete catalogue and keep every reservation within reach.</p>
                        </div>
                        <Link :href="routeUrl(canRegister ? 'register' : 'dashboard')" class="bf-button bf-button-primary">
                            Get started <i class="pi pi-arrow-right ml-2" />
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <Footer :app-name="appName" />
    </div>
</template>
