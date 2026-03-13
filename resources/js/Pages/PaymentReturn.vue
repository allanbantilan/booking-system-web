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

const resolvedStatus = computed(() => props.status || "pending");

const statusTone = computed(() => {
    if (resolvedStatus.value === "succeeded") return "text-emerald-300";
    if (resolvedStatus.value === "failed") return "text-rose-300";
    if (resolvedStatus.value === "cancelled") return "text-amber-300";
    return "text-slate-200";
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
    return "We're checking the payment status. This usually takes a few seconds.";
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
        <section class="rounded-2xl border border-white/10 bg-white/5 p-8">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">
                PayMaya Payment
            </p>
            <h1 class="mt-3 text-2xl font-bold" :class="statusTone">
                {{ statusHeadline }}
            </h1>
            <p class="mt-3 text-sm text-slate-300">
                {{ statusMessage }}
            </p>

            <div class="mt-6 rounded-xl border border-white/10 bg-slate-950/70 p-4 text-sm text-slate-200">
                <div class="flex items-center justify-between">
                    <span>Status</span>
                    <span class="font-semibold text-white">{{ resolvedStatus }}</span>
                </div>
                <div v-if="payment" class="mt-3 space-y-2 text-xs text-slate-300">
                    <p>Payment Reference: {{ payment.reference || "-" }}</p>
                    <p>Amount: {{ formatCurrency(payment.amount) }}</p>
                </div>
            </div>

            <div v-if="receipt" class="mt-6 rounded-2xl border border-emerald-400/40 bg-emerald-500/10 p-5 text-sm text-emerald-50">
                <p class="text-xs uppercase tracking-[0.3em] text-emerald-200">
                    E-Receipt
                </p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-emerald-200">Receipt Number</p>
                        <p class="text-base font-semibold">{{ receipt.receipt_number }}</p>
                    </div>
                    <div v-if="payment?.reservation?.customer">
                        <p class="text-xs text-emerald-200">Customer</p>
                        <p class="text-base font-semibold">{{ payment.reservation.customer.name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-emerald-200">Issued At</p>
                        <p class="text-base font-semibold">{{ formatDate(receipt.issued_at) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-emerald-200">Total Paid</p>
                        <p class="text-base font-semibold">{{ formatCurrency(receipt.amount) }}</p>
                    </div>
                    <div v-if="payment?.reservation?.booking">
                        <p class="text-xs text-emerald-200">Booking</p>
                        <p class="text-base font-semibold">
                            {{ payment.reservation.booking.title }}
                        </p>
                    </div>
                    <div v-if="payment?.reservation?.booking?.event_date">
                        <p class="text-xs text-emerald-200">Booking Date</p>
                        <p class="text-base font-semibold">
                            {{ formatDate(payment.reservation.booking.event_date) }}
                        </p>
                    </div>
                </div>
                <p class="mt-4 text-xs text-emerald-200">
                    Show this e-receipt to the admin when needed.
                </p>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <Link
                    :href="route('bookings.history')"
                    class="rounded-lg bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400"
                >
                    View booking history
                </Link>
                <Link
                    :href="route('bookings.index')"
                    class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10"
                >
                    Back to bookings
                </Link>
            </div>
        </section>
    </DashboardLayout>
</template>
