<script setup>
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Link, router } from "@inertiajs/vue3";
import { computed, reactive, watch } from "vue";
import { amenityConfig, getAccentClasses } from "@/Config/bookingCategoryConfig.js";

const props = defineProps({
    bookings: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            categoryId: "all",
            minPrice: "",
            maxPrice: "",
        }),
    },
});

const bookingQuantity = reactive({});
const expandedDescriptions = reactive({});
const filters = reactive({ ...props.filters });

const bookings = computed(() => props.bookings?.data || []);
const paginationLinks = computed(() => props.bookings?.links || []);

const categoryOptions = computed(() => {
    return props.categories.map((category) => ({
        id: String(category.id),
        name: category.name || "Category",
    }));
});

const applyFilters = () => {
    const payload = {
        categoryId: filters.categoryId !== "all" ? filters.categoryId : undefined,
        minPrice: filters.minPrice || undefined,
        maxPrice: filters.maxPrice || undefined,
    };

    router.get(route("bookings.index"), payload, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

let filterTimeout;
watch(
    () => ({ ...filters }),
    () => {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => {
            applyFilters();
        }, 300);
    },
    { deep: true },
);

watch(
    () => props.filters,
    (next) => {
        Object.assign(filters, next || {});
    },
    { deep: true },
);

const clearFilters = () => {
    filters.categoryId = "all";
    filters.minPrice = "";
    filters.maxPrice = "";
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

const openBookingScaffold = (bookingId) => {
    const quantity = Number(bookingQuantity[bookingId] || 1);

    router.get(
        route("bookings.show", bookingId),
        { quantity },
        { preserveState: false },
    );
};

const fallbackCategory = {
    color: "slate",
    badge_label: "Booking",
};

const getCategory = (booking) => booking.category || fallbackCategory;


const getAccent = (booking) => {
    const category = getCategory(booking);
    return getAccentClasses(category.color);
};

const getAmenities = (booking) => {
    const amenities = booking.amenities || [];
    return amenities.map((amenity) => ({
        key: amenity.amenity_key || amenity,
        ...amenityConfig[amenity.amenity_key || amenity],
    }));
};

const bookingTypeDefaults = {
    event: { availabilityLabel: "Tickets left", quantityLabel: "ticket(s)", nightsRequired: false },
    accommodation: { availabilityLabel: "Rooms left", quantityLabel: "room(s)", nightsRequired: true },
    service: { availabilityLabel: "Slots left", quantityLabel: "slot(s)", nightsRequired: false },
    rental: { availabilityLabel: "Units left", quantityLabel: "unit(s)", nightsRequired: true },
    package: { availabilityLabel: "Packages left", quantityLabel: "package(s)", nightsRequired: false },
};

const getTypeDefaults = (booking) => {
    const type = booking.booking_type || "event";
    return bookingTypeDefaults[type] || bookingTypeDefaults.event;
};

const getAvailabilityLabel = (booking) => {
    if (booking.availability_label) return booking.availability_label;
    return getTypeDefaults(booking).availabilityLabel;
};

const getAvailabilityValue = (booking) => {
    if (booking.capacity === null || booking.capacity === undefined) return null;
    return booking.capacity;
};

const getQuantityLabel = (booking) =>
    booking.quantity_label || getTypeDefaults(booking).quantityLabel;
const getCtaLabel = () => "View & Pay";
const getBadgeLabel = (booking) =>
    getCategory(booking).badge_label || "Booking";
const getMetaLine = (booking) => {
    if (booking.meta_line) return booking.meta_line;

    const location = booking.location || "-";
    const date = booking.event_date ? formatDate(booking.event_date) : null;

    return date ? `${location} · ${date}` : location;
};

const getRateLabel = (booking) => {
    const type = booking.booking_type || "event";
    if (type === "rental") return "Base Daily Rate";
    if (type === "accommodation") return "Base Nightly Rate";
    return "Price";
};

const getDiscountPercentage = (booking) =>
    Number(booking.discount_percentage || 0);

const getDiscountedPrice = (booking) => {
    const discount = getDiscountPercentage(booking);
    return booking.price * (1 - discount / 100);
};

const isLongDescription = (description) =>
    typeof description === "string" && description.length > 140;

const toggleDescription = (bookingId) => {
    expandedDescriptions[bookingId] = !expandedDescriptions[bookingId];
};
</script>

<template>
    <DashboardLayout title="Available Bookings">
        <section
            class="mb-5 rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur"
        >
            <h1 class="text-xl font-bold md:text-2xl">Available Bookings</h1>
            <p class="mt-1 text-sm text-slate-300">
                Browse available bookings and reserve with Maya payment.
            </p>
        </section>

        <section class="rounded-2xl border border-white/10 bg-white/5 p-6">
            <div class="mb-5 flex flex-wrap items-end gap-3">
                <div class="min-w-[200px]">
                    <label class="text-xs uppercase tracking-[0.2em] text-slate-400">
                        Category
                    </label>
                    <select
                        v-model="filters.categoryId"
                        class="mt-2 w-full rounded-lg border border-white/10 bg-slate-900 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400"
                    >
                        <option value="all">All categories</option>
                        <option
                            v-for="category in categoryOptions"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                </div>

                <div class="min-w-[140px]">
                    <label class="text-xs uppercase tracking-[0.2em] text-slate-400">
                        Min Price
                    </label>
                    <input
                        v-model="filters.minPrice"
                        type="number"
                        min="0"
                        class="mt-2 w-full rounded-lg border border-white/10 bg-slate-900 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400"
                        placeholder="0"
                    />
                </div>

                <div class="min-w-[140px]">
                    <label class="text-xs uppercase tracking-[0.2em] text-slate-400">
                        Max Price
                    </label>
                    <input
                        v-model="filters.maxPrice"
                        type="number"
                        min="0"
                        class="mt-2 w-full rounded-lg border border-white/10 bg-slate-900 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400"
                        placeholder="Any"
                    />
                </div>

                <button
                    type="button"
                    class="rounded-lg border border-white/20 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-200 transition hover:bg-white/10"
                    @click="clearFilters"
                >
                    Clear
                </button>
            </div>

            <div v-if="bookings.length" class="grid gap-5 md:grid-cols-2">
                <article
                    v-for="booking in bookings"
                    :key="booking.id"
                    class="group flex flex-col rounded-2xl border border-white/10 bg-slate-900/60 p-5 transition-all duration-300 hover:-translate-y-1 hover:border-white/20"
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
                                <template v-if="getAvailabilityValue(booking) !== null">
                                    {{ getAvailabilityLabel(booking) }}:
                                    {{ getAvailabilityValue(booking) }}
                                </template>
                                <template v-else>
                                    Availability not set
                                </template>
                            </span>
                        </div>

                        <p class="mt-1 text-sm text-slate-300">
                            {{ getMetaLine(booking) }}
                        </p>
                    </div>

                    <div class="mt-3 min-h-[4.5rem]">
                        <p
                            class="text-sm text-slate-300"
                            :style="
                                expandedDescriptions[booking.id]
                                    ? {}
                                    : {
                                          display: '-webkit-box',
                                          WebkitBoxOrient: 'vertical',
                                          WebkitLineClamp: 3,
                                          overflow: 'hidden',
                                      }
                            "
                        >
                            {{
                                booking.description ||
                                "No description provided yet."
                            }}
                        </p>
                        <button
                            v-if="isLongDescription(booking.description)"
                            type="button"
                            class="mt-2 text-xs font-semibold uppercase tracking-[0.2em] text-cyan-300 transition hover:text-cyan-200"
                            @click="toggleDescription(booking.id)"
                        >
                            {{ expandedDescriptions[booking.id] ? "Read Less" : "Read More" }}
                        </button>
                    </div>

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

                    <div class="mt-auto pt-5">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-400">
                                {{ getRateLabel(booking) }}
                            </p>
                            <div class="flex items-baseline gap-2">
                                <span class="text-lg font-black text-orange-300">
                                    {{ formatCurrency(getDiscountedPrice(booking)) }}
                                </span>
                                <span v-if="getDiscountPercentage(booking) > 0" class="text-xs text-slate-400 line-through">
                                    {{ formatCurrency(booking.price) }}
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
                                        getDiscountedPrice(booking) *
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
                    </div>
                </article>
            </div>

            <p
                v-else
                class="rounded-xl border border-dashed border-white/20 bg-slate-900/40 p-4 text-sm text-slate-300"
            >
                No bookings match your filters.
            </p>
        </section>

        <section v-if="paginationLinks.length > 1" class="mt-6 flex flex-wrap items-center justify-center gap-2">
            <component
                :is="link.url ? Link : 'span'"
                v-for="link in paginationLinks"
                :key="link.label"
                :href="link.url"
                preserve-scroll
                preserve-state
                class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold transition"
                :class="[
                    link.active
                        ? 'bg-cyan-500 text-slate-950 border-cyan-400'
                        : 'text-slate-200 hover:bg-white/10',
                    !link.url && 'cursor-not-allowed opacity-40',
                ]"
                v-html="link.label"
            />
        </section>
    </DashboardLayout>
</template>


