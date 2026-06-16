<script setup>
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    reservations: {
        type: Array,
        default: () => [],
    },
});

const activeReceipt = ref(null);
const activeCancellation = ref(null);
const cancellationForm = useForm({
    reason: "",
});

const closeReceipt = () => {
    activeReceipt.value = null;
};

const openReceipt = (reservation) => {
    activeReceipt.value = reservation;
};

const openCancellation = (reservation) => {
    activeCancellation.value = reservation;
    cancellationForm.reset();
    cancellationForm.clearErrors();
};

const closeCancellation = () => {
    activeCancellation.value = null;
    cancellationForm.reset();
    cancellationForm.clearErrors();
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

const bookingTypeDefaults = {
    event: { quantityLabel: "ticket(s)", nightsRequired: false, durationLabel: "Duration" },
    accommodation: { quantityLabel: "room(s)", nightsRequired: true, durationLabel: "Nights" },
    service: { quantityLabel: "slot(s)", nightsRequired: false, durationLabel: "Duration" },
    rental: { quantityLabel: "unit(s)", nightsRequired: true, durationLabel: "Days" },
    package: { quantityLabel: "package(s)", nightsRequired: false, durationLabel: "Duration" },
};

const getTypeDefaults = (reservation) => {
    const type = reservation.booking?.booking_type || "event";
    return bookingTypeDefaults[type] || bookingTypeDefaults.event;
};

const getQuantityLabel = (reservation) => {
    return reservation.booking?.quantity_label || getTypeDefaults(reservation).quantityLabel;
};

const isNightsRequired = (reservation) => getTypeDefaults(reservation).nightsRequired;
const getDurationLabel = (reservation) => getTypeDefaults(reservation).durationLabel || "Duration";

const getCancellationLabel = (reservation) => {
    if (reservation.cancellation_request?.status === "requested") return "Cancellation requested";
    if (reservation.cancellation_request?.status === "approved") return "Cancellation approved";
    if (reservation.cancellation_request?.status === "rejected") return "Cancellation rejected";
    if (reservation.cancellation_request?.status === "expired") return "Request expired";
    if (reservation.can_cancel) return "Request Cancellation";
    if (reservation.cancellation_eligibility?.block_label) return reservation.cancellation_eligibility.block_label;
    if (reservation.status === "cancelled") return "Cancelled";

    return "Cancellation unavailable";
};

const getCancellationTitle = (reservation) => {
    if (reservation.cancellation_request?.status === "requested") {
        return reservation.cancellation_request.expires_at
            ? `Merchant review pending until ${formatDateTime(reservation.cancellation_request.expires_at)}`
            : "Merchant review pending";
    }

    if (reservation.cancellation_request?.status === "approved") {
        return reservation.cancellation_request.refund_status === "pending"
            ? "Refund pending merchant/payment processing."
            : "Cancellation approved.";
    }

    if (reservation.cancellation_request?.status === "rejected") {
        return reservation.cancellation_request.merchant_note || "Merchant rejected this cancellation request.";
    }

    if (reservation.cancellation_request?.status === "expired") {
        return "Merchant did not review before the cancellation cutoff.";
    }

    if (reservation.cancellation_eligibility?.policy_label) {
        return reservation.cancellation_eligibility.policy_label;
    }

    if (reservation.status === "cancelled") {
        return reservation.cancelled_at
            ? `Cancelled ${formatDateTime(reservation.cancelled_at)}`
            : "Reservation already cancelled";
    }

    if (reservation.can_cancel) return "Request cancellation for merchant review.";

    return "Cancellation is unavailable.";
};

const submitCancellation = () => {
    if (!activeCancellation.value) return;

    cancellationForm.post(
        route(
            "reservations.cancellation-requests.store",
            activeCancellation.value.id,
        ),
        {
            preserveScroll: true,
            onSuccess: () => closeCancellation(),
        },
    );
};
</script>

<template>
    <DashboardLayout title="Booking History">
        <section
            class="mb-5 rounded-xl border border-ledger-border bg-ledger-surface p-4 backdrop-blur"
        >
            <h1 class="text-xl font-bold md:text-2xl">Booking History</h1>
            <p class="mt-1 text-sm text-ledger-muted">
                Track your pending and confirmed bookings.
            </p>
        </section>

        <section class="rounded-2xl border border-ledger-border bg-ledger-surface p-6">
            <div v-if="reservations.length" class="overflow-x-auto">
                <table
                    class="min-w-full border-separate border-spacing-y-2 text-sm"
                >
                    <thead>
                        <tr class="text-left text-ledger-muted">
                            <th class="px-3 py-2">Booking</th>
                            <th class="px-3 py-2">Booking Date</th>
                            <th class="px-3 py-2">Total</th>
                            <th class="px-3 py-2">Duration</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Receipt</th>
                            <th class="px-3 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="reservation in reservations"
                            :key="reservation.id"
                            class="rounded-xl border border-ledger-border bg-ledger-surface/60"
                        >
                            <td class="px-3 py-3">
                                {{ reservation.booking?.title || "Booking" }}
                            </td>
                            <td class="px-3 py-3 text-ledger-muted">
                                {{ formatDate(reservation.booking?.event_date) }}
                            </td>
                            <td class="px-3 py-3">
                                {{ formatCurrency(reservation.total_price) }}
                            </td>
                            <td class="px-3 py-3 text-ledger-muted">
                                <span v-if="isNightsRequired(reservation)">
                                    {{ reservation.nights || 1 }} {{ getDurationLabel(reservation) }}
                                </span>
                                <span v-else>-</span>
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="
                                        reservation.status === 'confirmed'
                                            ? 'bg-emerald-400/20 text-emerald-300'
                                            : reservation.status === 'cancelled'
                                                ? 'bg-rose-400/20 text-rose-300'
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
                                    class="inline-flex items-center rounded-full border border-ledger-border bg-ledger-surface px-3 py-1 text-xs font-semibold text-cyan-200 transition hover:border-cyan-400 hover:text-cyan-100"
                                    @click="openReceipt(reservation)"
                                >
                                    View Receipt
                                </button>
                                <span v-else class="text-xs text-ledger-muted">
                                    -
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <button
                                    v-if="reservation.can_cancel"
                                    type="button"
                                    @click="openCancellation(reservation)"
                                    class="rounded-lg border border-ledger-border px-3 py-1.5 text-xs font-semibold transition hover:bg-ledger-elevated"
                                >
                                    Request Cancellation
                                </button>
                                <span
                                    v-else
                                    class="inline-flex items-center rounded-full border border-ledger-border bg-ledger-elevated px-3 py-1.5 text-xs font-semibold text-ledger-muted"
                                    :title="getCancellationTitle(reservation)"
                                >
                                    {{ getCancellationLabel(reservation) }}
                                </span>
                                <p
                                    v-if="reservation.cancellation_request?.status === 'approved' && reservation.cancellation_request?.refund_required"
                                    class="mt-1 text-xs text-ledger-muted"
                                >
                                    Refund {{ reservation.cancellation_request.refund_status }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p
                v-else
                class="rounded-xl border border-dashed border-ledger-border bg-ledger-surface/40 p-4 text-sm text-ledger-muted"
            >
                No booking history yet.
            </p>
        </section>
    </DashboardLayout>

    <div
        v-if="activeCancellation"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 px-4 py-6"
        @click.self="closeCancellation"
    >
        <form
            class="w-full max-w-lg rounded-2xl border border-ledger-border bg-ledger-surface p-6 shadow-xl"
            @submit.prevent="submitCancellation"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-ledger-muted">
                        Cancellation request
                    </p>
                    <h2 class="mt-2 text-xl font-semibold text-ledger-text">
                        {{ activeCancellation.booking?.title || "Booking" }}
                    </h2>
                    <p class="mt-1 text-sm text-ledger-muted">
                        Merchant approval is required before cancellation and refund.
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-full border border-ledger-border p-2 text-ledger-text transition hover:bg-ledger-elevated"
                    @click="closeCancellation"
                >
                    ✕
                </button>
            </div>

            <label class="mt-5 block text-sm font-medium text-ledger-text">
                Reason
                <textarea
                    v-model="cancellationForm.reason"
                    rows="4"
                    class="mt-2 w-full rounded-lg border border-ledger-border bg-ledger-surface p-3 text-sm text-ledger-text placeholder:text-ledger-muted focus:border-cyan-400 focus:outline-none focus:ring-1 focus:ring-cyan-400"
                    placeholder="Optional note for the merchant"
                ></textarea>
            </label>
            <p
                v-if="cancellationForm.errors.reason"
                class="mt-2 text-sm text-rose-300"
            >
                {{ cancellationForm.errors.reason }}
            </p>
            <p
                v-if="cancellationForm.errors.error"
                class="mt-2 text-sm text-rose-300"
            >
                {{ cancellationForm.errors.error }}
            </p>

            <div class="mt-5 flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-ledger-border px-4 py-2 text-sm font-semibold text-ledger-text transition hover:bg-ledger-elevated"
                    @click="closeCancellation"
                >
                    Close
                </button>
                <button
                    type="submit"
                    :disabled="cancellationForm.processing"
                    class="rounded-lg bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400 disabled:cursor-not-allowed disabled:opacity-70"
                >
                    {{ cancellationForm.processing ? "Submitting..." : "Submit Request" }}
                </button>
            </div>
        </form>
    </div>

    <div
        v-if="activeReceipt"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 px-4 py-6"
        @click.self="closeReceipt"
    >
        <div class="w-full max-w-xl rounded-2xl border border-ledger-border bg-ledger-surface p-6 shadow-xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-ledger-muted">E-Receipt</p>
                    <h2 class="mt-2 text-xl font-semibold text-ledger-text">
                        {{ activeReceipt.booking?.title || "Booking" }}
                    </h2>
                    <p class="mt-1 text-sm text-ledger-muted">
                        Reservation #{{ activeReceipt.id }}
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-full border border-ledger-border p-2 text-ledger-text transition hover:bg-ledger-elevated"
                    @click="closeReceipt"
                >
                    ✕
                </button>
            </div>

            <div class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                <div class="rounded-xl border border-ledger-border bg-ledger-surface p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-ledger-muted">Receipt</p>
                    <p class="mt-2 text-base font-semibold text-ledger-text">
                        {{ activeReceipt.receipt?.receipt_number || "-" }}
                    </p>
                    <p class="mt-1 text-xs text-ledger-muted">
                        Issued {{ formatDateTime(activeReceipt.receipt?.issued_at) }}
                    </p>
                </div>
                <div class="rounded-xl border border-ledger-border bg-ledger-surface p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-ledger-muted">Payment Status</p>
                    <p class="mt-2 text-base font-semibold text-ledger-text">
                        {{ receiptStatus }}
                    </p>
                    <p class="mt-1 text-xs text-ledger-muted">
                        Amount {{ formatCurrency(activeReceipt.total_price) }}
                    </p>
                </div>
            </div>

            <div class="mt-6 rounded-xl border border-ledger-border bg-slate-950/60 p-4 text-sm text-ledger-text">
                <div class="flex items-center justify-between">
                    <span>Booking Date</span>
                    <span class="font-semibold text-ledger-text">
                        {{ formatDate(activeReceipt.booking?.event_date) }}
                    </span>
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <span>{{ getQuantityLabel(activeReceipt) }}</span>
                    <span class="font-semibold text-ledger-text">
                        {{ activeReceipt.quantity }}
                    </span>
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <span>{{ getDurationLabel(activeReceipt) }}</span>
                    <span class="font-semibold text-ledger-text">
                        {{ isNightsRequired(activeReceipt) ? (activeReceipt.nights || 1) : "-" }}
                    </span>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-ledger-border px-4 py-2 text-sm font-semibold text-ledger-text transition hover:bg-ledger-elevated"
                    @click="closeReceipt"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</template>
