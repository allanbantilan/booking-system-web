<script setup>
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { router } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    reservations: {
        type: Array,
        default: () => [],
    },
});

const activeReceipt = ref(null);

const closeReceipt = () => {
    activeReceipt.value = null;
};

const openReceipt = (reservation) => {
    activeReceipt.value = reservation;
};

const receiptStatus = computed(() => activeReceipt.value?.payment?.status || "pending");

const formatDateTime = (value) => {
    if (!value) return "-";

    return new Date(value).toLocaleString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "numeric",
        minute: "2-digit",
    });
};

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
                Track your pending and confirmed bookings.
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
                            <th class="px-3 py-2">Total</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Receipt</th>
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
                                {{ formatCurrency(reservation.total_price) }}
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="
                                        reservation.status === 'confirmed'
                                            ? 'bg-emerald-400/20 text-emerald-300'
                                            : 'bg-amber-400/20 text-amber-300'
                                    "
                                >
                                    {{ reservation.status }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <button
                                    v-if="reservation.receipt && reservation.payment?.id"
                                    type="button"
                                    class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-cyan-200 transition hover:border-cyan-400 hover:text-cyan-100"
                                    @click="openReceipt(reservation)"
                                >
                                    View Receipt
                                </button>
                                <span v-else class="text-xs text-slate-400">
                                    -
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

    <div
        v-if="activeReceipt"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-4 py-6"
        @click.self="closeReceipt"
    >
        <div class="w-full max-w-xl rounded-2xl border border-white/10 bg-slate-900 p-6 shadow-xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">E-Receipt</p>
                    <h2 class="mt-2 text-xl font-semibold text-white">
                        {{ activeReceipt.booking?.title || "Booking" }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-300">
                        Reservation #{{ activeReceipt.id }}
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-full border border-white/10 p-2 text-slate-200 transition hover:bg-white/10"
                    @click="closeReceipt"
                >
                    ✕
                </button>
            </div>

            <div class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Receipt</p>
                    <p class="mt-2 text-base font-semibold text-white">
                        {{ activeReceipt.receipt?.receipt_number || "-" }}
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        Issued {{ formatDateTime(activeReceipt.receipt?.issued_at) }}
                    </p>
                </div>
                <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Payment Status</p>
                    <p class="mt-2 text-base font-semibold text-white">
                        {{ receiptStatus }}
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        Amount {{ formatCurrency(activeReceipt.total_price) }}
                    </p>
                </div>
            </div>

            <div class="mt-6 rounded-xl border border-white/10 bg-slate-950/60 p-4 text-sm text-slate-200">
                <div class="flex items-center justify-between">
                    <span>Booking Date</span>
                    <span class="font-semibold text-white">
                        {{ formatDate(activeReceipt.booking?.event_date) }}
                    </span>
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <span>{{ activeReceipt.booking?.quantity_label || "Quantity" }}</span>
                    <span class="font-semibold text-white">
                        {{ activeReceipt.quantity }}
                    </span>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10"
                    @click="closeReceipt"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</template>
