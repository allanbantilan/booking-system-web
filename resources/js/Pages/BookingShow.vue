<script setup>
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import ImageViewer from "@/Components/Bookings/ImageViewer.vue";
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
const activeGallery = ref(null);

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
    const label = props.booking.quantity_label || getTypeDefaults().quantityLabel;
    const clean = String(label).replace(/\(s\)/g, "s");

    return clean.charAt(0).toUpperCase() + clean.slice(1);
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

const stayLengthLabel = computed(() => {
    if (!requiresDateRange.value) return null;

    const unit = props.booking.booking_type === "rental" ? "day" : "night";
    const count = stayLength.value;

    return `${count} ${unit}${count === 1 ? "" : "s"}`;
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

const openGallery = (index = 0) => {
    activeGallery.value = { index };
};

const closeGallery = () => {
    activeGallery.value = null;
};

const contactItems = computed(() => {
    const creator = props.booking.creator || {};

    return [
        {
            key: "email",
            label: "Email",
            value: creator.email,
            url: creator.email ? `mailto:${creator.email}` : null,
        },
        {
            key: "mobile",
            label: "Mobile",
            value: creator.mobile_number,
            url: creator.mobile_number ? `tel:${creator.mobile_number}` : null,
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
        <section class="mb-6 border-b border-ledger-border pb-5">
            <h1 class="text-xl font-bold md:text-2xl">
                {{ booking.title }}
            </h1>
            <p class="mt-1 text-sm text-ledger-muted">
                <span>{{ booking.location }}</span>
                <span v-if="booking.event_date">
                    - {{ formatDate(booking.event_date) }}
                </span>
            </p>
        </section>

        <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr] lg:items-start">
            <section class="ledger-panel p-5 sm:p-6">
                <button
                    v-if="primaryImage"
                    type="button"
                    class="group relative mb-4 block w-full overflow-hidden rounded-lg border border-ledger-border bg-ledger-elevated text-left"
                    @click="openGallery(0)"
                >
                    <img
                        :src="primaryImage"
                        :alt="booking.title"
                        class="h-72 w-full object-cover transition duration-300 group-hover:scale-[1.01]"
                    />
                    <span
                        class="absolute left-3 top-3 rounded-md border border-ledger-border bg-ledger-surface px-3 py-1 text-xs font-semibold text-ledger-text"
                    >
                        {{ booking.image_urls?.length || 1 }}
                        photo{{ (booking.image_urls?.length || 1) === 1 ? "" : "s" }}
                    </span>
                </button>

                <div
                    v-if="booking.image_urls?.length > 1"
                    class="grid grid-cols-3 gap-3"
                >
                    <button
                        v-for="(image, index) in booking.image_urls"
                        :key="`${booking.id}-image-${index}`"
                        type="button"
                        class="overflow-hidden rounded-lg border border-ledger-border bg-ledger-elevated transition hover:border-ledger-primary"
                        @click="openGallery(index)"
                    >
                        <img
                            :src="image"
                            :alt="`${booking.title} image ${index + 1}`"
                            class="h-24 w-full object-cover"
                            loading="lazy"
                        />
                    </button>
                </div>

                <div class="mt-6 space-y-4 text-sm text-ledger-text">
                    <p class="text-base text-ledger-text">
                        {{ booking.description || "No description yet." }}
                    </p>
                    <div class="flex flex-wrap gap-3 text-xs">
                        <span
                            v-if="booking.category?.name"
                            class="rounded-md border border-ledger-border bg-ledger-surface px-3 py-1 text-ledger-primary"
                        >
                            {{ booking.category.name }}
                        </span>
                        <span
                            class="rounded-md border border-ledger-border bg-ledger-surface px-3 py-1 text-ledger-text"
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

            <aside class="ledger-panel sticky top-20 p-5 sm:p-6">
                <h2 class="font-display text-xl font-bold text-ledger-text">
                    Booking summary
                </h2>
                <div class="mt-4 grid gap-4">
                    <div class="rounded-lg border border-ledger-border bg-ledger-elevated p-4">
                        <div class="flex items-start justify-between gap-4">
                            <span class="text-sm text-ledger-muted">{{ getRateLabel() }}</span>
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
                                    class="text-xs text-ledger-muted line-through"
                                >
                                    {{ formatCurrency(booking.price) }}
                                </div>
                                <div v-if="getDiscountPercentage() > 0" class="text-xs font-semibold text-emerald-300">
                                    -{{ getDiscountPercentage() }}%
                                </div>
                            </div>
                        </div>
                        <div
                            v-if="isNightsRequired && booking.extra_rate"
                            class="mt-4 flex items-start justify-between gap-4 border-t border-ledger-border pt-4"
                        >
                            <span class="text-sm text-ledger-muted">{{ getExtraRateLabel() }}</span>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-ledger-text">
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

                        <div class="mt-4 border-t border-ledger-border pt-4">
                            <p class="text-xs font-semibold text-ledger-muted">
                                Merchant
                            </p>
                            <div class="mt-3 rounded-lg border border-ledger-border bg-ledger-surface px-3 py-2">
                                <span class="text-xs text-ledger-muted">Created by</span>
                                <p class="mt-1 truncate text-sm font-semibold text-ledger-text">
                                    {{ booking.creator?.name || "Admin" }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div
                                v-for="item in contactItems"
                                :key="item.key"
                                class="rounded-lg border border-ledger-border bg-ledger-surface p-3 text-sm"
                            >
                                <span
                                    class="inline-flex items-center gap-2 text-xs text-ledger-muted"
                                >
                                    <span
                                        v-if="item.key === 'email'"
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-ledger-border bg-ledger-elevated text-ledger-text"
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
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-ledger-border bg-ledger-elevated text-ledger-text"
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
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-ledger-border bg-ledger-elevated text-ledger-text"
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
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-ledger-border bg-ledger-elevated text-ledger-text"
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
                                <a
                                    v-if="item.url"
                                    :href="item.url"
                                    :target="item.key === 'facebook' || item.key === 'instagram' ? '_blank' : undefined"
                                    :rel="item.key === 'facebook' || item.key === 'instagram' ? 'noopener' : undefined"
                                    class="mt-2 block truncate font-semibold text-ledger-primary transition hover:text-ledger-text"
                                >
                                    {{ item.key === "facebook" || item.key === "instagram" ? "Open profile" : item.value }}
                                </a>
                                <span v-else class="mt-2 block truncate font-semibold text-ledger-text">
                                    {{ item.value || "Not set" }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-ledger-border bg-ledger-elevated p-4">
                        <div class="space-y-3">
                            <label class="text-sm font-semibold text-ledger-text">
                                {{ getQuantityLabel() }}
                            </label>
                            <input
                                v-model.number="quantity"
                                type="number"
                                min="1"
                                class="bf-field w-full px-3 py-2 text-sm outline-none"
                            />
                            <div v-if="requiresDateRange" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                                <div>
                                    <label class="text-sm text-ledger-muted">Check-in</label>
                                    <input
                                        v-model="checkInDate"
                                        type="date"
                                        class="bf-field w-full px-3 py-2 text-sm outline-none"
                                    />
                                </div>
                                <div>
                                    <label class="text-sm text-ledger-muted">Check-out</label>
                                    <input
                                        v-model="checkOutDate"
                                        type="date"
                                        class="bf-field w-full px-3 py-2 text-sm outline-none"
                                    />
                                </div>
                                <div class="rounded-lg border border-ledger-border bg-ledger-surface px-3 py-2 text-sm sm:col-span-2 xl:col-span-1 2xl:col-span-2">
                                    <span class="text-ledger-muted">Duration</span>
                                    <span class="ml-2 font-semibold text-ledger-text">
                                        {{ isDateRangeInvalid ? "Select valid dates" : stayLengthLabel }}
                                    </span>
                                </div>
                            </div>
                            <div v-else-if="isNightsRequired">
                                <label class="text-sm text-ledger-muted">{{ getDurationLabel() }}</label>
                                <input
                                    v-model.number="nights"
                                    type="number"
                                    min="1"
                                class="bf-field w-full px-3 py-2 text-sm outline-none"
                                />
                            </div>
                        </div>

                        <div class="mt-4 rounded-lg border border-ledger-border bg-ledger-surface p-4 text-ledger-text">
                            <div class="flex items-center justify-between text-sm">
                                <span>Total</span>
                                <span class="font-semibold">
                                    {{ formatCurrency(totalPrice) }}
                                </span>
                            </div>
                            <p class="mt-2 text-xs">
                                You will be redirected to PayMaya to complete
                                payment.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="startPayMayaCheckout"
                            :disabled="isProcessing || isDateRangeInvalid"
                            class="bf-button bf-button-primary mt-4 w-full disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            {{
                                isProcessing
                                    ? "Preparing checkout..."
                                    : "Reserve Now"
                            }}
                        </button>

                        <p
                            v-if="page.props.flash?.error"
                            class="mt-3 text-xs text-rose-300"
                        >
                            {{ page.props.flash?.error }}
                        </p>
                    </div>
                </div>
            </aside>
        </div>

        <ImageViewer
            :open="!!activeGallery"
            :images="booking.image_urls || []"
            :title="booking.title"
            :initial-index="activeGallery?.index || 0"
            @close="closeGallery"
        />
    </DashboardLayout>
</template>
