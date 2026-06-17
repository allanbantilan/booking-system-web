<script setup>
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    checkoutId: {
        type: String,
        default: "",
    },
    status: {
        type: String,
        default: "",
    },
    payment: {
        type: Object,
        default: null,
    },
    receipt: {
        type: Object,
        default: null,
    },
});

const normalizeStatus = (value) => {
    if (!value) return "pending";

    const normalized = String(value).toLowerCase();

    if (["success", "succeeded"].includes(normalized)) return "succeeded";
    if (["failed", "failure"].includes(normalized)) return "failed";
    if (["cancel", "cancelled", "canceled"].includes(normalized)) return "cancelled";

    return normalized;
};

const resolvedStatus = computed(() => {
    if (props.receipt) return "succeeded";
    if (props.payment?.status) return normalizeStatus(props.payment.status);
    return normalizeStatus(props.status);
});

const statusTone = computed(() => {
    if (resolvedStatus.value === "succeeded") return "text-ledger-text";
    if (resolvedStatus.value === "failed") return "text-rose-300";
    if (resolvedStatus.value === "cancelled") return "text-orange-300";
    return "text-ledger-text";
});

const paymentPanelClass = computed(() => {
    if (resolvedStatus.value === "succeeded") return "bf-payment-panel-success";
    if (resolvedStatus.value === "failed") return "bf-payment-panel-failed";
    if (resolvedStatus.value === "cancelled") return "bf-payment-panel-warning";
    return "";
});

const paymentStatusClass = computed(() =>
    resolvedStatus.value === "succeeded" ? "bf-payment-status-success" : "",
);

const paymentDotClass = computed(() => {
    if (resolvedStatus.value === "succeeded") return "bg-ledger-text";
    if (resolvedStatus.value === "failed") return "bg-rose-400";
    if (resolvedStatus.value === "cancelled") return "bg-orange-400";
    return "bg-ledger-muted animate-pulse";
});

const statusHeadline = computed(() => {
    if (resolvedStatus.value === "succeeded") return "Payment confirmed";
    if (resolvedStatus.value === "failed") return "Payment failed";
    if (resolvedStatus.value === "cancelled") return "Payment cancelled";
    return "Waiting for confirmation";
});

const statusMessage = computed(() => {
    if (resolvedStatus.value === "succeeded") {
        return "Your reservation is confirmed. You can show this e-receipt to the admin.";
    }
    if (resolvedStatus.value === "failed") {
        return "We could not confirm your payment. Please try again.";
    }
    if (resolvedStatus.value === "cancelled") {
        return "The payment was cancelled. You can start a new checkout anytime.";
    }
    return "We're confirming your payment. This usually takes a few seconds.";
});

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: props.payment?.currency || "PHP",
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
</script>

<template>
    <DashboardLayout title="Payment Status">
        <section class="bf-payment-panel p-6 sm:p-8" :class="paymentPanelClass">
            <p class="text-xs font-semibold text-ledger-muted">PayMaya payment</p>
            <h1 class="mt-3 text-2xl font-bold" :class="statusTone">
                {{ statusHeadline }}
            </h1>
            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                <span
                    class="bf-payment-status"
                    :class="paymentStatusClass"
                >
                    <span
                        class="h-2 w-2 rounded-full"
                        :class="paymentDotClass"
                    ></span>
                    {{ resolvedStatus }}
                </span>
                <span v-if="payment?.reference" class="text-ledger-muted">
                    Ref: {{ payment.reference }}
                </span>
            </div>
            <p class="mt-3 text-sm text-ledger-muted">
                {{ statusMessage }}
            </p>

            <div class="mt-6 rounded-xl border border-ledger-border bg-ledger-surface p-4 text-sm text-ledger-text">
                <div class="flex items-center justify-between">
                    <span>Status</span>
                    <span class="font-semibold text-ledger-text">{{ resolvedStatus }}</span>
                </div>
                <div v-if="payment" class="mt-3 space-y-2 text-xs text-ledger-muted">
                    <p>Payment Reference: {{ payment.reference || "-" }}</p>
                    <p>Amount: {{ formatCurrency(payment.amount) }}</p>
                </div>
            </div>

            <div v-if="receipt" class="mt-6 rounded-xl border border-ledger-border bg-ledger-surface p-5 text-sm text-ledger-text">
                <p class="text-xs font-semibold text-ledger-muted">E-Receipt</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-ledger-muted">Receipt Number</p>
                        <p class="text-base font-semibold">{{ receipt.receipt_number }}</p>
                    </div>
                    <div v-if="payment?.reservation?.customer">
                        <p class="text-xs text-ledger-muted">Customer</p>
                        <p class="text-base font-semibold">{{ payment.reservation.customer.name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ledger-muted">Issued At</p>
                        <p class="text-base font-semibold">{{ formatDate(receipt.issued_at) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ledger-muted">Total Paid</p>
                        <p class="text-base font-semibold">{{ formatCurrency(receipt.amount) }}</p>
                    </div>
                    <div v-if="payment?.reservation?.booking">
                        <p class="text-xs text-ledger-muted">Booking</p>
                        <p class="text-base font-semibold">
                            {{ payment.reservation.booking.title }}
                        </p>
                    </div>
                    <div v-if="payment?.reservation?.booking?.event_date">
                        <p class="text-xs text-ledger-muted">Booking Date</p>
                        <p class="text-base font-semibold">
                            {{ formatDate(payment.reservation.booking.event_date) }}
                        </p>
                    </div>
                </div>
                <p class="mt-4 text-xs text-ledger-muted">
                    Show this e-receipt to the admin when needed.
                </p>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <Link
                    :href="route('bookings.history')"
                    class="bf-button bf-button-payment"
                >
                    View booking history
                </Link>
                <Link
                    :href="route('bookings.index')"
                    class="bf-button bf-button-secondary"
                >
                    Back to bookings
                </Link>
            </div>
        </section>
    </DashboardLayout>
</template>
