<script setup>
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { router } from "@inertiajs/vue3";
import { reactive } from "vue";

defineProps({
    events: {
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

const openBookingScaffold = (eventId) => {
    const quantity = Number(bookingQuantity[eventId] || 1);

    router.post(route("bookings.store", eventId), {
        quantity,
    });
};
</script>

<template>
    <DashboardLayout title="Events">
        <section
            class="mb-5 rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur"
        >
            <h1 class="text-xl font-bold md:text-2xl">Events</h1>
            <p class="mt-1 text-sm text-slate-300">
                Browse available events and book tickets.
            </p>
        </section>

        <section class="rounded-2xl border border-white/10 bg-white/5 p-6">
            <div v-if="events.length" class="grid gap-4 md:grid-cols-2">
                <article
                    v-for="event in events"
                    :key="event.id"
                    class="rounded-xl border border-white/10 bg-slate-900/60 p-5"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-bold text-white">
                                {{ event.title }}
                            </h3>
                            <p class="mt-1 text-sm text-slate-300">
                                {{ event.location }} •
                                {{ formatDate(event.event_date) }}
                            </p>
                        </div>
                        <span
                            class="rounded-full border border-white/20 px-3 py-1 text-xs text-cyan-300"
                        >
                            Seat Limit: {{ event.capacity }}
                        </span>
                    </div>

                    <p class="mt-3 text-sm text-slate-300">
                        {{ event.description || "No description provided yet." }}
                    </p>

                    <div class="mt-4 flex items-center justify-between gap-4">
                        <p class="text-lg font-black text-orange-300">
                            {{ formatCurrency(event.price) }}
                        </p>
                        <div class="flex items-center gap-2">
                            <input
                                v-model.number="bookingQuantity[event.id]"
                                type="number"
                                min="1"
                                class="w-20 rounded-lg border border-white/20 bg-slate-900 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400"
                                placeholder="1"
                            />
                            <button
                                type="button"
                                @click="openBookingScaffold(event.id)"
                                class="rounded-lg bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400"
                            >
                                Book Ticket
                            </button>
                        </div>
                    </div>
                </article>
            </div>

            <p
                v-else
                class="rounded-xl border border-dashed border-white/20 bg-slate-900/40 p-4 text-sm text-slate-300"
            >
                No events yet. Create events in Filament, then they will appear
                here.
            </p>
        </section>
    </DashboardLayout>
</template>
