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
    <section class="pt-52 pb-32 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
        <!-- Background Decoration -->
        <div class="absolute inset-0 overflow-hidden">
            <div
                class="absolute -top-40 -right-40 w-80 h-80 bg-cyan-500/30 rounded-full mix-blend-multiply filter blur-3xl opacity-70"
            ></div>
            <div
                class="absolute -bottom-40 -left-40 w-80 h-80 bg-orange-500/30 rounded-full mix-blend-multiply filter blur-3xl opacity-70"
            ></div>
        </div>

        <div class="max-w-7xl mx-auto relative">
            <div class="text-center">
                <h1
                    class="text-5xl md:text-7xl font-bold text-white mb-6 leading-tight"
                >
                    Book appointments and
                    <span
                        class="bg-gradient-to-r from-cyan-400 to-orange-400 bg-clip-text text-transparent"
                    >
                        services
                    </span>
                </h1>
                <p class="text-xl text-slate-200 mb-10 max-w-3xl mx-auto">
                    A complete booking system for teams that need live
                    schedules, real-time slot availability, reminders, and
                    smooth checkout.
                </p>

                <div
                    class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto"
                >
                    <div
                        v-for="(stat, index) in stats"
                        :key="index"
                        class="rounded-xl border border-white/10 bg-white/5 px-4 py-4"
                    >
                        <p class="text-cyan-300 font-semibold text-lg">
                            {{ stat.value }}
                        </p>
                        <p class="text-slate-300 text-sm">{{ stat.label }}</p>
                    </div>
                </div>

                <div
                    class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4"
                >
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="bg-cyan-500 hover:bg-cyan-400 text-slate-950 px-8 py-3 rounded-lg font-semibold shadow-md hover:shadow-lg transition-all duration-200"
                    >
                        Start Booking
                    </Link>
                    <Link
                        v-if="!canRegister"
                        :href="route('login')"
                        class="bg-cyan-500 hover:bg-cyan-400 text-slate-950 px-8 py-3 rounded-lg font-semibold shadow-md hover:shadow-lg transition-all duration-200"
                    >
                        Open Dashboard
                    </Link>
                    <Link
                        :href="route('login')"
                        class="px-8 py-3 rounded-lg border border-white/20 text-white hover:bg-white/10 transition-colors"
                    >
                        Sign In
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>
