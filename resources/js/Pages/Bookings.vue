<script setup>
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Link, router } from "@inertiajs/vue3";
import { reactive } from "vue";

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
            <div v-if="bookings.length" class="grid gap-4 md:grid-cols-2">
                <article
                    v-for="booking in bookings"
                    :key="booking.id"
                    class="rounded-xl border border-white/10 bg-slate-900/60 p-5"
                >
                    <div
                        v-if="booking.image_urls?.length"
                        class="mb-4 overflow-hidden rounded-xl border border-white/10"
                    >
                        <img
                            :src="booking.image_urls[0]"
                            :alt="booking.title"
                            class="h-40 w-full object-cover"
                            loading="lazy"
                        />
                    </div>

                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-bold text-white">
                                {{ booking.title }}
                            </h3>
                            <p class="mt-1 text-sm text-slate-300">
                                {{ booking.location }} -
                                {{ formatDate(booking.event_date) }}
                            </p>
                            <p
                                v-if="booking.category?.name"
                                class="mt-1 text-xs uppercase tracking-[0.2em] text-cyan-200"
                            >
                                {{ booking.category.name }}
                            </p>
                        </div>
                        <span
                            class="rounded-full border border-white/20 px-3 py-1 text-xs text-cyan-300"
                        >
                            Seat Limit: {{ booking.capacity }}
                        </span>
                    </div>

                    <p class="mt-3 text-sm text-slate-300">
                        {{
                            booking.description ||
                            "No description provided yet."
                        }}
                    </p>

                    <div class="mt-4 flex items-center justify-between gap-4">
                        <p class="text-lg font-black text-orange-300">
                            {{ formatCurrency(booking.price) }}
                        </p>
                        <div class="flex items-center gap-2">
                            <input
                                v-model.number="bookingQuantity[booking.id]"
                                type="number"
                                min="1"
                                class="w-20 rounded-lg border border-white/20 bg-slate-900 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400"
                                placeholder="1"
                            />
                            <button
                                type="button"
                                @click="openBookingScaffold(booking.id)"
                                class="rounded-lg bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400"
                            >
                                Reserve
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
                No bookings yet.
            </p>
        </section>
    </DashboardLayout>
</template>
