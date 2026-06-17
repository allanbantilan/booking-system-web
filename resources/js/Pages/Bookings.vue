<script setup>
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Link, router } from "@inertiajs/vue3";
import { computed, reactive, ref, watch } from "vue";
import { amenityConfig, getAccentClasses } from "@/Config/bookingCategoryConfig.js";
import ImageViewer from "@/Components/Bookings/ImageViewer.vue";

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
            search: "",
            categoryId: "all",
            minPrice: "",
            maxPrice: "",
        }),
    },
});

const expandedDescriptions = reactive({});
const filters = reactive({ ...props.filters });
const activeGallery = ref(null);

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
        search: filters.search || undefined,
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
    filters.search = "";
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

const goToBookingDetails = (bookingId) => {
    router.get(route("bookings.show", bookingId), {}, { preserveState: false });
};

const openGallery = (booking, index = 0) => {
    activeGallery.value = { booking, index };
};

const closeGallery = () => {
    activeGallery.value = null;
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

const getCtaLabel = () => "View Details";
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
        <section class="mb-5 border-b border-ledger-border pb-5">
            <h1 class="text-xl font-bold md:text-2xl">Available Bookings</h1>
            <p class="mt-1 text-sm text-ledger-muted">
                Browse available bookings and reserve with Maya payment.
            </p>
        </section>

        <section class="ledger-panel p-5 sm:p-6">
            <div class="mb-5 flex flex-wrap items-end gap-3">
                <div class="min-w-[260px] flex-1">
                    <label class="text-xs font-semibold text-ledger-muted">
                        Search
                    </label>
                    <input
                        v-model="filters.search"
                        type="search"
                        class="bf-field mt-2 w-full px-3 py-2 text-sm placeholder:text-ledger-muted outline-none"
                        placeholder="Search title, location, or description"
                    />
                </div>

                <div class="min-w-[200px]">
                    <label class="text-xs font-semibold text-ledger-muted">
                        Category
                    </label>
                    <select
                        v-model="filters.categoryId"
                        class="bf-field mt-2 w-full px-3 py-2 text-sm outline-none"
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
                    <label class="text-xs font-semibold text-ledger-muted">
                        Min Price
                    </label>
                    <input
                        v-model="filters.minPrice"
                        type="number"
                        min="0"
                        class="bf-field mt-2 w-full px-3 py-2 text-sm outline-none"
                        placeholder="0"
                    />
                </div>

                <div class="min-w-[140px]">
                    <label class="text-xs font-semibold text-ledger-muted">
                        Max Price
                    </label>
                    <input
                        v-model="filters.maxPrice"
                        type="number"
                        min="0"
                        class="bf-field mt-2 w-full px-3 py-2 text-sm outline-none"
                        placeholder="Any"
                    />
                </div>

                <button
                    type="button"
                    class="bf-button bf-button-secondary"
                    @click="clearFilters"
                >
                    Clear
                </button>
            </div>

            <div v-if="bookings.length" class="grid gap-5 sm:grid-cols-2 2xl:grid-cols-3">
                <article
                    v-for="booking in bookings"
                    :key="booking.id"
                    role="link"
                    tabindex="0"
                    class="bf-market-card group flex cursor-pointer flex-col p-3 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-400"
                    :class="getAccent(booking).glow"
                    @click="goToBookingDetails(booking.id)"
                    @keydown.enter.prevent="goToBookingDetails(booking.id)"
                    @keydown.space.prevent="goToBookingDetails(booking.id)"
                >
                    <button
                        v-if="booking.image_urls?.length"
                        type="button"
                        class="bf-market-photo relative mb-4 block w-full overflow-hidden text-left"
                        @click.stop="openGallery(booking, 0)"
                    >
                        <img
                            :src="booking.image_urls[0]"
                            :alt="booking.title"
                            class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.015]"
                            loading="lazy"
                        />
                        <span
                            v-if="booking.category?.name"
                            class="absolute right-3 top-3 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold leading-none"
                            :class="getAccent(booking).badge"
                        >
                            <span>{{ getBadgeLabel(booking) }}</span>
                        </span>
                    </button>

                    <div
                        v-else
                        class="bf-market-photo mb-4 flex items-center justify-center border border-dashed border-ledger-border text-sm text-ledger-muted"
                    >
                        No image available
                    </div>

                    <div
                        class="px-1"
                        :class="getAccent(booking).border"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="text-base font-bold text-ledger-text">
                                {{ booking.title }}
                            </h3>
                            <span
                                class="shrink-0 rounded-full border border-ledger-border px-3 py-1 text-xs font-semibold text-ledger-text"
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

                        <p class="mt-1 text-sm text-ledger-muted">
                            {{ getMetaLine(booking) }}
                        </p>
                    </div>

                    <div class="mt-3 min-h-[4.5rem] px-1">
                        <p
                            class="text-sm text-ledger-muted"
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
                            class="mt-2 text-xs font-semibold text-ledger-primary transition hover:text-ledger-text"
                            @click.stop="toggleDescription(booking.id)"
                        >
                            {{ expandedDescriptions[booking.id] ? "Read Less" : "Read More" }}
                        </button>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2 px-1">
                        <div
                            v-for="amenity in getAmenities(booking)"
                            :key="amenity.key"
                            class="flex items-center gap-1 rounded-full border border-ledger-border bg-ledger-surface px-3 py-1 text-xs text-ledger-text leading-none"
                        >
                            <span class="inline-flex leading-none">{{ amenity.icon }}</span>
                            <span>{{ amenity.label }}</span>
                        </div>
                    </div>

                    <div class="mt-auto px-1 pt-5">
                        <div class="flex flex-wrap items-end justify-between gap-4 border-t border-ledger-border pt-4">
                            <div>
                                <p class="text-xs font-semibold text-ledger-muted">
                                    {{ getRateLabel(booking) }}
                                </p>
                                <div class="mt-1 flex flex-wrap items-baseline gap-2">
                                    <span class="bf-price">
                                        {{ formatCurrency(getDiscountedPrice(booking)) }}
                                    </span>
                                    <span v-if="getDiscountPercentage(booking) > 0" class="text-xs text-ledger-muted line-through">
                                        {{ formatCurrency(booking.price) }}
                                    </span>
                                    <span v-if="getDiscountPercentage(booking) > 0" class="text-xs font-semibold text-emerald-300">
                                        -{{ getDiscountPercentage(booking) }}%
                                    </span>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="rounded-lg px-0 py-2 text-sm font-semibold transition"
                                :class="getAccent(booking).button"
                                @click.stop="goToBookingDetails(booking.id)"
                            >
                                {{ getCtaLabel(booking) }}
                            </button>
                        </div>
                    </div>
                </article>
            </div>

            <p
                v-else
                class="rounded-xl border border-dashed border-ledger-border bg-ledger-surface/40 p-4 text-sm text-ledger-muted"
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
                class="rounded-lg border border-ledger-border px-3 py-1.5 text-xs font-semibold transition"
                :class="[
                    link.active
                        ? 'bg-cyan-500 text-slate-950 border-cyan-400'
                        : 'text-ledger-text hover:bg-ledger-elevated',
                    !link.url && 'cursor-not-allowed opacity-40',
                ]"
                v-html="link.label"
            />
        </section>

        <ImageViewer
            :open="!!activeGallery"
            :images="activeGallery?.booking?.image_urls || []"
            :title="activeGallery?.booking?.title || 'Booking image'"
            :initial-index="activeGallery?.index || 0"
            @close="closeGallery"
        />
    </DashboardLayout>
</template>
