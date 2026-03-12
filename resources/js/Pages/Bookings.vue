<script setup>
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Link, router } from "@inertiajs/vue3";
import { reactive } from "vue";
import { amenityConfig, getAccentClasses } from "@/Config/bookingCategoryConfig.js";

defineProps({
    bookings: {
        type: Array,
        default: () => [],
    },
});

const bookingQuantity = reactive({});

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

const openBookingScaffold = (bookingId) => {
    const quantity = Number(bookingQuantity[bookingId] || 1);

    router.post(route("reservations.store", bookingId), {
        quantity,
    });
};

const fallbackCategory = {
    color: "slate",
    badge_label: "Booking",
    quantity_label: "slot(s)",
    availability_label: "Slots left",
    meta_line: "Available booking slot",
    amenities: ["availability", "support", "secure"],
};

const getCategory = (booking) => booking.category || fallbackCategory;


const getAccent = (booking) => {
    const category = getCategory(booking);
    return getAccentClasses(category.color);
};

const getAmenities = (booking) => {
    const category = getCategory(booking);
    const amenities = category.amenities || [];
    return amenities.map((amenity) => ({
        key: amenity.amenity_key || amenity,
        ...amenityConfig[amenity.amenity_key || amenity],
    }));
};

const getAvailabilityLabel = (booking) =>
    getCategory(booking).availability_label || "Slots left";
const getQuantityLabel = (booking) =>
    getCategory(booking).quantity_label || "slot(s)";
const getCtaLabel = () => "Book Now";
const getBadgeLabel = (booking) =>
    getCategory(booking).badge_label || "Booking";
const getMetaLine = (booking) =>
    getCategory(booking).meta_line ||
    `${booking.location || "-"} · ${formatDate(booking.event_date)}`;

const getDiscountPercentage = (booking) =>
    Number(booking.discount_percentage || 0);

const getOriginalPrice = (booking) => {
    const discount = getDiscountPercentage(booking);
    return booking.price * (1 + discount / 100);
};
</script>

<template>
    <DashboardLayout title="Bookings">
        <section
            class="mb-5 rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur"
        >
            <h1 class="text-xl font-bold md:text-2xl">Bookings</h1>
            <p class="mt-1 text-sm text-slate-300">
                Browse available bookings and reserve your slot.
            </p>
        </section>

        <section class="rounded-2xl border border-white/10 bg-white/5 p-6">
            <div v-if="bookings.length" class="grid gap-5 md:grid-cols-2">
                <article
                    v-for="booking in bookings"
                    :key="booking.id"
                    class="group rounded-2xl border border-white/10 bg-slate-900/60 p-5 transition-all duration-300 hover:-translate-y-1 hover:border-white/20"
                    :class="getAccent(booking).glow"
                >
                    <div
                        v-if="booking.image_urls?.length"
                        class="relative mb-4 overflow-hidden rounded-xl border border-white/10"
                    >
                        <img
                            :src="booking.image_urls[0]"
                            :alt="booking.title"
                            class="h-44 w-full object-cover transition duration-300 group-hover:scale-105"
                            loading="lazy"
                        />
                        <span
                            v-if="booking.category?.name"
                            class="absolute right-3 top-3 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] leading-none"
                            :class="getAccent(booking).badge"
                        >
                            <span>{{ getBadgeLabel(booking) }}</span>
                        </span>
                    </div>

                    <div
                        class="border-t-4 pt-4"
                        :class="getAccent(booking).border"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="text-lg font-bold text-white">
                                {{ booking.title }}
                            </h3>
                            <span
                                class="rounded-full border border-white/20 px-3 py-1 text-xs text-slate-200"
                            >
                                {{ getAvailabilityLabel(booking) }}:
                                {{ booking.capacity }}
                            </span>
                        </div>

                        <p class="mt-1 text-sm text-slate-300">
                            {{ getMetaLine(booking) }}
                        </p>
                    </div>

                    <p class="mt-3 text-sm text-slate-300">
                        {{
                            booking.description ||
                            "No description provided yet."
                        }}
                    </p>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <div
                            v-for="amenity in getAmenities(booking)"
                            :key="amenity.key"
                            class="flex items-center gap-1 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-200 leading-none"
                        >
                            <span class="inline-flex leading-none">{{ amenity.icon }}</span>
                            <span>{{ amenity.label }}</span>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-400">
                                Price
                            </p>
                            <div class="flex items-baseline gap-2">
                                <span class="text-lg font-black text-orange-300">
                                    {{ formatCurrency(booking.price) }}
                                </span>
                                <span v-if="getDiscountPercentage(booking) > 0" class="text-xs text-slate-400 line-through">
                                    {{ formatCurrency(getOriginalPrice(booking)) }}
                                </span>
                                <span v-if="getDiscountPercentage(booking) > 0" class="text-[10px] uppercase tracking-[0.2em] text-emerald-300">
                                    -{{ getDiscountPercentage(booking) }}%
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1">
                                <button
                                    type="button"
                                    class="h-7 w-7 rounded-full border border-white/10 text-sm font-semibold text-white transition hover:bg-white/10"
                                    @click="
                                        bookingQuantity[booking.id] =
                                            Math.max(
                                                1,
                                                (bookingQuantity[booking.id] || 1) - 1,
                                            )
                                    "
                                >
                                    -
                                </button>
                                <span class="text-sm text-white">
                                    {{ bookingQuantity[booking.id] || 1 }}
                                </span>
                                <button
                                    type="button"
                                    class="h-7 w-7 rounded-full border border-white/10 text-sm font-semibold text-white transition hover:bg-white/10"
                                    @click="
                                        bookingQuantity[booking.id] =
                                            (bookingQuantity[booking.id] || 1) + 1
                                    "
                                >
                                    +
                                </button>
                                <span class="text-xs text-slate-300">
                                    {{ getQuantityLabel(booking) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm text-slate-300">
                            Total:
                            <span class="font-semibold text-white">
                                {{
                                    formatCurrency(
                                        booking.price *
                                            Number(bookingQuantity[booking.id] || 1),
                                    )
                                }}
                            </span>
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                @click="openBookingScaffold(booking.id)"
                                class="rounded-lg px-4 py-2 text-sm font-semibold transition shadow-sm"
                                :class="getAccent(booking).button"
                            >
                                {{ getCtaLabel(booking) }}
                            </button>
                            <Link
                                :href="route('bookings.show', booking.id)"
                                class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10"
                            >
                                View Details
                            </Link>
                        </div>
                    </div>
                </article>
            </div>

            <p
                v-else
                class="rounded-xl border border-dashed border-white/20 bg-slate-900/40 p-4 text-sm text-slate-300"
            >
                No bookings yet. Create bookings in Filament, then they will
                appear here.
            </p>
        </section>
    </DashboardLayout>
</template>


