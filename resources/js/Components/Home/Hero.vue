<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    canRegister: {
        type: Boolean,
    },
    stats: {
        type: Array,
        default: () => [
            { value: "24/7", label: "Self-service bookings" },
            { value: "Instant", label: "Availability checks" },
            { value: "Auto", label: "Reminders and updates" },
            { value: "One place", label: "Calendar and payments" },
        ],
    },
});
</script>

<template>
    <section class="px-4 pb-24 pt-32 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto relative">
            <div class="max-w-3xl">
                <h1
                    class="mb-6 font-display text-5xl font-bold leading-tight text-ledger-text md:text-7xl"
                >
                    Book appointments and services.
                </h1>
                <p class="mb-10 max-w-2xl text-xl text-ledger-muted">
                    A complete booking system for teams that need live
                    schedules, real-time slot availability, reminders, and
                    smooth checkout.
                </p>

                <div
                    class="grid max-w-4xl grid-cols-2 gap-4 md:grid-cols-4"
                >
                    <div
                        v-for="(stat, index) in stats"
                        :key="index"
                        class="rounded-lg border border-ledger-border bg-ledger-surface px-4 py-4"
                    >
                        <p class="text-lg font-semibold text-ledger-primary">
                            {{ stat.value }}
                        </p>
                        <p class="text-sm text-ledger-muted">{{ stat.label }}</p>
                    </div>
                </div>

                <div
                    class="mt-10 flex flex-col gap-4 sm:flex-row"
                >
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="bf-button bf-button-primary"
                    >
                        Start Booking
                    </Link>
                    <Link
                        v-if="!canRegister"
                        :href="route('login')"
                        class="bf-button bf-button-primary"
                    >
                        Open Dashboard
                    </Link>
                    <Link
                        :href="route('login')"
                        class="bf-button bf-button-secondary"
                    >
                        Sign In
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>
