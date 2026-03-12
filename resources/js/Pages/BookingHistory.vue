<script setup>
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { router } from "@inertiajs/vue3";

defineProps({
    bookings: {
        type: Array,
        default: () => [],
    },
});

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(Number(value || 0));

const formatDate = (value) => {
    if (!value) return "-";

    return new Date(value).toLocaleString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "numeric",
        minute: "2-digit",
    });
};

const cancelBookingScaffold = (bookingId) => {
    router.patch(route("bookings.cancel", bookingId));
};
</script>

<template>
    <DashboardLayout title="Booking History">
        <section
            class="mb-5 rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur"
        >
            <h1 class="text-xl font-bold md:text-2xl">Booking History</h1>
            <p class="mt-1 text-sm text-slate-300">
                Track your confirmed and cancelled bookings.
            </p>
        </section>

        <section class="rounded-2xl border border-white/10 bg-white/5 p-6">
            <div v-if="bookings.length" class="overflow-x-auto">
                <table
                    class="min-w-full border-separate border-spacing-y-2 text-sm"
                >
                    <thead>
                        <tr class="text-left text-slate-300">
                            <th class="px-3 py-2">Event</th>
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Quantity</th>
                            <th class="px-3 py-2">Total</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="booking in bookings"
                            :key="booking.id"
                            class="rounded-xl border border-white/10 bg-slate-900/60"
                        >
                            <td class="px-3 py-3">
                                {{ booking.event?.title || "Event" }}
                            </td>
                            <td class="px-3 py-3 text-slate-300">
                                {{ formatDate(booking.event?.event_date) }}
                            </td>
                            <td class="px-3 py-3">
                                {{ booking.quantity }}
                            </td>
                            <td class="px-3 py-3">
                                {{ formatCurrency(booking.total_price) }}
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="
                                        booking.status === 'confirmed'
                                            ? 'bg-emerald-400/20 text-emerald-300'
                                            : 'bg-rose-400/20 text-rose-300'
                                    "
                                >
                                    {{ booking.status }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <button
                                    type="button"
                                    :disabled="booking.status === 'cancelled'"
                                    @click="cancelBookingScaffold(booking.id)"
                                    class="rounded-lg border border-white/20 px-3 py-1.5 text-xs font-semibold transition enabled:hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    Cancel Booking
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p
                v-else
                class="rounded-xl border border-dashed border-white/20 bg-slate-900/40 p-4 text-sm text-slate-300"
            >
                No booking history yet.
            </p>
        </section>
    </DashboardLayout>
</template>
