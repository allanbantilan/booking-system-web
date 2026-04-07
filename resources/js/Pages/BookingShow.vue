<script setup>
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    booking: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const initialQuantity = Number(
    new URLSearchParams(window.location.search).get("quantity") || 1,
);
const initialNights = Number(
    new URLSearchParams(window.location.search).get("nights") || 1,
);
const quantity = ref(
    Number.isFinite(initialQuantity) && initialQuantity > 0
        ? initialQuantity
        : 1,
);
const nights = ref(
    Number.isFinite(initialNights) && initialNights > 0 ? initialNights : 1,
);
const checkInDate = ref("");
const checkOutDate = ref("");
const isProcessing = ref(false);

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
    event: { availabilityLabel: "Tickets left", quantityLabel: "ticket(s)", nightsRequired: false, durationLabel: "Duration" },
    accommodation: { availabilityLabel: "Rooms left", quantityLabel: "room(s)", nightsRequired: true, durationLabel: "Nights" },
    service: { availabilityLabel: "Slots left", quantityLabel: "slot(s)", nightsRequired: false, durationLabel: "Duration" },
    rental: { availabilityLabel: "Units left", quantityLabel: "unit(s)", nightsRequired: true, durationLabel: "Days" },
    package: { availabilityLabel: "Packages left", quantityLabel: "package(s)", nightsRequired: false, durationLabel: "Duration" },
};

const getTypeDefaults = () => {
    const type = props.booking.booking_type || "event";
    return bookingTypeDefaults[type] || bookingTypeDefaults.event;
};

const getAvailabilityLabel = () => {
    if (props.booking.availability_label) return props.booking.availability_label;
    return getTypeDefaults().availabilityLabel;
};

const getAvailabilityValue = () => {
    if (props.booking.capacity === null || props.booking.capacity === undefined) return null;
    return props.booking.capacity;
};

const getQuantityLabel = () => {
    if (props.booking.quantity_label) return props.booking.quantity_label;
    return getTypeDefaults().quantityLabel;
};

const isNightsRequired = computed(() => getTypeDefaults().nightsRequired);
const requiresDateRange = computed(() =>
    ["rental", "accommodation"].includes(props.booking.booking_type || "event"),
);
const isDateRangeInvalid = computed(() => {
    if (!requiresDateRange.value) return false;
    if (!checkInDate.value || !checkOutDate.value) return true;

    const start = new Date(`${checkInDate.value}T00:00:00Z`);
    const end = new Date(`${checkOutDate.value}T00:00:00Z`);

    return end.getTime() <= start.getTime();
});
const getDurationLabel = () => getTypeDefaults().durationLabel || "Duration";

const getDiscountPercentage = () =>
    Number(props.booking.discount_percentage || 0);

const getDiscountedBasePrice = () => {
    const discount = getDiscountPercentage();
    return props.booking.price * (1 - discount / 100);
};

const getDiscountedExtraRate = () => {
    if (props.booking.extra_rate === null || props.booking.extra_rate === undefined) {
        return null;
    }

    const discount = getDiscountPercentage();
    return props.booking.extra_rate * (1 - discount / 100);
};

const stayLength = computed(() => {
    if (!requiresDateRange.value) {
        return isNightsRequired.value ? Number(nights.value || 1) : 1;
    }

    if (!checkInDate.value || !checkOutDate.value) return 1;

    const start = new Date(`${checkInDate.value}T00:00:00Z`);
    const end = new Date(`${checkOutDate.value}T00:00:00Z`);
    const diffMs = end.getTime() - start.getTime();
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    return diffDays > 0 ? diffDays : 1;
});

const totalPrice = computed(() => {
    const unitCount = Number(quantity.value || 1);
    const stay = stayLength.value;
    const discount = getDiscountPercentage();
    const basePrice = props.booking.price * (1 - discount / 100);

    if (!isNightsRequired.value) {
        return basePrice * unitCount;
    }

    if (props.booking.extra_rate === null || props.booking.extra_rate === undefined) {
        return basePrice * unitCount * stay;
    }

    const extraNights = Math.max(0, stay - 1);
    const extraRate = props.booking.extra_rate * (1 - discount / 100);

    return (basePrice * unitCount) + (extraRate * unitCount * extraNights);
});

const getRateLabel = () => {
    if (!isNightsRequired.value) return "Price";
    return props.booking.booking_type === "rental" ? "Base Daily Rate" : "Base Nightly Rate";
};

const getExtraRateLabel = () => {
    return props.booking.booking_type === "rental" ? "Extra Day Rate" : "Extra Night Rate";
};

const primaryImage = computed(() => props.booking.image_urls?.[0]);

const contactItems = computed(() => {
    const creator = props.booking.creator || {};

    return [
        {
            key: "email",
            label: "Email",
            value: creator.email,
        },
        {
            key: "mobile",
            label: "Mobile",
            value: creator.mobile_number,
        },
        {
            key: "facebook",
            label: "Facebook",
            value: creator.facebook_url,
            url: creator.facebook_url,
        },
        {
            key: "instagram",
            label: "Instagram",
            value: creator.instagram_url,
            url: creator.instagram_url,
        },
    ];
});

const startPayMayaCheckout = () => {
    isProcessing.value = true;

    const payload = {
        booking_id: props.booking.id,
        quantity: Number(quantity.value || 1),
        check_in_date: requiresDateRange.value ? checkInDate.value : null,
        check_out_date: requiresDateRange.value ? checkOutDate.value : null,
    };

    if (!requiresDateRange.value) {
        payload.nights = Number(nights.value || 1);
    }

    router.post(
        route("payments.paymaya.checkout"),
        payload,
        {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
            },
        },
    );
};
</script>

<template>
    <DashboardLayout :title="booking.title">
        <section
            class="mb-6 rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur"
        >
            <h1 class="text-xl font-bold md:text-2xl">
                {{ booking.title }}
            </h1>
            <p class="mt-1 text-sm text-slate-300">
                <span>{{ booking.location }}</span>
                <span v-if="booking.event_date">
                    - {{ formatDate(booking.event_date) }}
                </span>
            </p>
        </section>

        <div class="grid gap-6 lg:grid-cols-[1.2fr,0.8fr]">
            <section class="rounded-2xl border border-white/10 bg-white/5 p-6">
                <div
                    v-if="primaryImage"
                    class="mb-4 overflow-hidden rounded-2xl border border-white/10"
                >
                    <img
                        :src="primaryImage"
                        :alt="booking.title"
                        class="h-72 w-full object-cover"
                    />
                </div>

                <div
                    v-if="booking.image_urls?.length > 1"
                    class="grid grid-cols-3 gap-3"
                >
                    <div
                        v-for="(image, index) in booking.image_urls"
                        :key="`${booking.id}-image-${index}`"
                        class="overflow-hidden rounded-xl border border-white/10"
                    >
                        <img
                            :src="image"
                            :alt="`${booking.title} image ${index + 1}`"
                            class="h-24 w-full object-cover"
                            loading="lazy"
                        />
                    </div>
                </div>

                <div class="mt-6 space-y-4 text-sm text-slate-200">
                    <p class="text-base text-white">
                        {{ booking.description || "No description yet." }}
                    </p>
                    <div class="flex flex-wrap gap-3 text-xs uppercase">
                        <span
                            v-if="booking.category?.name"
                            class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-cyan-200"
                        >
                            {{ booking.category.name }}
                        </span>
                        <span
                            class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-slate-200"
                        >
                            <template v-if="getAvailabilityValue() !== null">
                                {{ getAvailabilityLabel() }}:
                                {{ getAvailabilityValue() }}
                            </template>
                            <template v-else>
                                Availability not set
                            </template>
                        </span>
                    </div>
                </div>
            </section>

            <aside class="rounded-2xl border border-white/10 bg-white/5 p-6">
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">
                    Booking Summary
                </p>
                <div class="mt-4 space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-sm text-slate-300">{{ getRateLabel() }}</span>
                        <div class="text-right">
                            <div class="text-lg font-black text-orange-300">
                                <template v-if="getDiscountPercentage() > 0">
                                    {{ formatCurrency(getDiscountedBasePrice()) }}
                                </template>
                                <template v-else>
                                    {{ formatCurrency(booking.price) }}
                                </template>
                            </div>
                            <div
                                v-if="getDiscountPercentage() > 0"
                                class="text-xs text-slate-400 line-through"
                            >
                                {{ formatCurrency(booking.price) }}
                            </div>
                            <div
                                v-if="getDiscountPercentage() > 0"
                                class="text-[10px] uppercase tracking-[0.2em] text-emerald-300"
                            >
                                -{{ getDiscountPercentage() }}%
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="isNightsRequired && booking.extra_rate"
                        class="flex items-start justify-between gap-4"
                    >
                        <span class="text-sm text-slate-300">{{ getExtraRateLabel() }}</span>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-slate-200">
                                <template v-if="getDiscountPercentage() > 0">
                                    {{ formatCurrency(getDiscountedExtraRate()) }}
                                </template>
                                <template v-else>
                                    {{ formatCurrency(booking.extra_rate) }}
                                </template>
                            </div>
                            <div
                                v-if="getDiscountPercentage() > 0"
                                class="text-xs text-slate-500 line-through"
                            >
                                {{ formatCurrency(booking.extra_rate) }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-300">Created By</span>
                        <span class="text-sm text-white">
                            {{ booking.creator?.name || "Admin" }}
                        </span>
                    </div>
                    <div class="space-y-3">
                        <div
                            v-for="item in contactItems"
                            :key="item.key"
                            class="flex items-center justify-between gap-3 text-sm"
                        >
                            <span
                                class="inline-flex items-center gap-2 text-slate-300"
                            >
                                <span
                                    v-if="item.key === 'email'"
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-white/10 bg-white/5 text-slate-200"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        class="h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    >
                                        <path d="M4 6h16v12H4z" />
                                        <path d="M4 7l8 6 8-6" />
                                    </svg>
                                </span>
                                <span
                                    v-else-if="item.key === 'mobile'"
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-white/10 bg-white/5 text-slate-200"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        class="h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    >
                                        <path d="M7 2h10v20H7z" />
                                        <path d="M10 19h4" />
                                    </svg>
                                </span>
                                <span
                                    v-else-if="item.key === 'facebook'"
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-white/10 bg-white/5 text-slate-200"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        class="h-4 w-4"
                                        fill="currentColor"
                                    >
                                        <path
                                            d="M13.5 9H16V6h-2.5C11.6 6 11 7.5 11 9v2H9v3h2v6h3v-6h2.2L17 11h-3V9c0-.6.2-1 1.5-1z"
                                        />
                                    </svg>
                                </span>
                                <span
                                    v-else-if="item.key === 'instagram'"
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-white/10 bg-white/5 text-slate-200"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        class="h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    >
                                        <rect
                                            x="4"
                                            y="4"
                                            width="16"
                                            height="16"
                                            rx="5"
                                        />
                                        <circle cx="12" cy="12" r="3.5" />
                                        <circle
                                            cx="17"
                                            cy="7"
                                            r="1"
                                            fill="currentColor"
                                            stroke="none"
                                        />
                                    </svg>
                                </span>
                                <span>{{ item.label }}</span>
                            </span>
                            <span class="text-white">
                                <!-- Facebook & Instagram: icon button, no URL text -->
                                <a
                                    v-if="
                                        item.url &&
                                        (item.key === 'facebook' ||
                                            item.key === 'instagram')
                                    "
                                    :href="item.url"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-cyan-200 transition hover:bg-white/10 hover:text-cyan-100"
                                >
                                    <svg
                                        v-if="item.key === 'facebook'"
                                        viewBox="0 0 24 24"
                                        class="h-3.5 w-3.5"
                                        fill="currentColor"
                                    >
                                        <path
                                            d="M13.5 9H16V6h-2.5C11.6 6 11 7.5 11 9v2H9v3h2v6h3v-6h2.2L17 11h-3V9c0-.6.2-1 1.5-1z"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        viewBox="0 0 24 24"
                                        class="h-3.5 w-3.5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    >
                                        <rect
                                            x="4"
                                            y="4"
                                            width="16"
                                            height="16"
                                            rx="5"
                                        />
                                        <circle cx="12" cy="12" r="3.5" />
                                        <circle
                                            cx="17"
                                            cy="7"
                                            r="1"
                                            fill="currentColor"
                                            stroke="none"
                                        />
                                    </svg>
                                    Visit {{ item.label }}
                                </a>
                                <span
                                    v-else-if="
                                        item.url &&
                                        (item.key === 'facebook' ||
                                            item.key === 'instagram')
                                    "
                                    class="text-xs text-slate-500 italic"
                                >
                                    Not set
                                </span>
                                <!-- Email & Mobile: show value as plain text or link -->
                                <a
                                    v-else-if="item.url"
                                    :href="item.url"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-cyan-200 hover:text-cyan-100"
                                >
                                    {{ item.value }}
                                </a>
                                <span v-else>
                                    {{
                                        item.value || "Available after booking"
                                    }}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    <label class="text-sm text-slate-300">
                        {{ getQuantityLabel() }}
                    </label>
                    <input
                        v-model.number="quantity"
                        type="number"
                        min="1"
                        class="w-full rounded-lg border border-white/20 bg-slate-900 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400"
                    />
                    <div v-if="requiresDateRange" class="space-y-3">
                        <div>
                            <label class="text-sm text-slate-300">Check-in</label>
                            <input
                                v-model="checkInDate"
                                type="date"
                                class="w-full rounded-lg border border-white/20 bg-slate-900 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400"
                            />
                        </div>
                        <div>
                            <label class="text-sm text-slate-300">Check-out</label>
                            <input
                                v-model="checkOutDate"
                                type="date"
                                class="w-full rounded-lg border border-white/20 bg-slate-900 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400"
                            />
                        </div>
                    </div>
                    <div v-else-if="isNightsRequired">
                        <label class="text-sm text-slate-300">{{ getDurationLabel() }}</label>
                        <input
                            v-model.number="nights"
                            type="number"
                            min="1"
                            class="w-full rounded-lg border border-white/20 bg-slate-900 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400"
                        />
                    </div>

                    <div
                        class="rounded-xl border border-white/10 bg-slate-950/70 p-4"
                    >
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-300">Total</span>
                            <span class="font-semibold text-white">
                                {{ formatCurrency(totalPrice) }}
                            </span>
                        </div>
                        <p class="mt-2 text-xs text-slate-400">
                            You will be redirected to PayMaya to complete
                            payment.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="startPayMayaCheckout"
                        :disabled="isProcessing || isDateRangeInvalid"
                        class="w-full rounded-lg bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400 disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        {{
                            isProcessing
                                ? "Preparing checkout..."
                                : "Reserve Now"
                        }}
                    </button>

                    <p
                        v-if="page.props.flash?.error"
                        class="text-xs text-rose-300"
                    >
                        {{ page.props.flash?.error }}
                    </p>
                </div>
            </aside>
        </div>
    </DashboardLayout>
</template>
