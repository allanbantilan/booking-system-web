<script setup>
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { router } from "@inertiajs/vue3";

defineProps({
    reservations: {
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

const cancelBookingScaffold = (reservationId) => {
    router.patch(route("reservations.cancel", reservationId));
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
            <div v-if="reservations.length" class="overflow-x-auto">
                <table
                    class="min-w-full border-separate border-spacing-y-2 text-sm"
                >
                    <thead>
                        <tr class="text-left text-slate-300">
                            <th class="px-3 py-2">Booking</th>
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Quantity</th>
                            <th class="px-3 py-2">Total</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="reservation in reservations"
                            :key="reservation.id"
                            class="rounded-xl border border-white/10 bg-slate-900/60"
                        >
                            <td class="px-3 py-3">
                                {{ reservation.booking?.title || "Booking" }}
                            </td>
                            <td class="px-3 py-3 text-slate-300">
                                {{ formatDate(reservation.booking?.event_date) }}
                            </td>
                            <td class="px-3 py-3">
                                {{ reservation.quantity }}
                            </td>
                            <td class="px-3 py-3">
                                {{ formatCurrency(reservation.total_price) }}
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="
                                        reservation.status === 'confirmed'
                                            ? 'bg-emerald-400/20 text-emerald-300'
                                            : 'bg-rose-400/20 text-rose-300'
                                    "
                                >
                                    {{ reservation.status }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <button
                                    type="button"
                                    :disabled="reservation.status === 'cancelled'"
                                    @click="cancelBookingScaffold(reservation.id)"
                                    class="rounded-lg border border-white/20 px-3 py-1.5 text-xs font-semibold transition enabled:hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    Cancel Reservation
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
