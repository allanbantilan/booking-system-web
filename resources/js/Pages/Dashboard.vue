<script setup>
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { router } from "@inertiajs/vue3";

defineProps({
    totals: {
        type: Object,
        default: () => ({
            bookings: 0,
            bookingHistory: 0,
            confirmedBookings: 0,
        }),
    },
});

</script>

<template>
    <DashboardLayout :title="`Booking Dashboard`">
        <section
            class="mb-5 rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur"
        >
            <h1 class="text-xl font-bold md:text-2xl">
                Booking Dashboard
            </h1>
            <p class="mt-1 text-sm text-slate-300">
                Track high-level activity and jump into bookings or booking
                history.
            </p>
        </section>

        <section class="mb-8 grid gap-4 sm:grid-cols-3">
            <article
                class="rounded-xl border border-white/10 bg-white/5 p-5"
            >
                <p
                    class="text-xs uppercase tracking-[0.15em] text-slate-300"
                >
                    Total Bookings
                </p>
                <p class="mt-2 text-3xl font-black text-cyan-300">
                    {{ totals.bookings }}
                </p>
            </article>
            <article
                class="rounded-xl border border-white/10 bg-white/5 p-5"
            >
                <p
                    class="text-xs uppercase tracking-[0.15em] text-slate-300"
                >
                    Booking History
                </p>
                <p class="mt-2 text-3xl font-black text-cyan-300">
                    {{ totals.bookingHistory }}
                </p>
            </article>
            <article
                class="rounded-xl border border-white/10 bg-white/5 p-5"
            >
                <p
                    class="text-xs uppercase tracking-[0.15em] text-slate-300"
                >
                    Confirmed Bookings
                </p>
                <p class="mt-2 text-3xl font-black text-cyan-300">
                    {{ totals.confirmedBookings }}
                </p>
            </article>
        </section>

        <section class="grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                <h2 class="text-xl font-bold">Manage Bookings</h2>
                <p class="mt-2 text-sm text-slate-300">
                    Browse all booking options, view details, and reserve.
                </p>
                <button
                    type="button"
                    class="mt-4 rounded-lg bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400"
                    @click="router.visit(route('bookings.index'))"
                >
                    Go to Bookings
                </button>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                <h2 class="text-xl font-bold">Booking History</h2>
                <p class="mt-2 text-sm text-slate-300">
                    Review confirmed and cancelled bookings in one place.
                </p>
                <button
                    type="button"
                    class="mt-4 rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10"
                    @click="router.visit(route('bookings.history'))"
                >
                    View History
                </button>
            </div>
        </section>
    </DashboardLayout>
</template>
