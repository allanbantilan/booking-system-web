<script setup>
import Navbar from "@/Components/Dashboard/Navbar.vue";
import Sidebar from "@/Components/Dashboard/Sidebar.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { computed, onMounted, onUnmounted, reactive, ref } from "vue";

const props = defineProps({
    events: {
        type: Array,
        default: () => [],
    },
    bookings: {
        type: Array,
        default: () => [],
    },
    totals: {
        type: Object,
        default: () => ({
            events: 0,
            bookingHistory: 0,
            confirmedBookings: 0,
        }),
    },
});

const page = usePage();
const appName = computed(() => page.props.appName || "BookFlow");
const activeSidebarKey = ref("overview");

const sidebarLinks = [
    { key: "overview", label: "Dashboard Overview", href: "#top" },
    { key: "events", label: "Events", href: "#events" },
    { key: "history", label: "Booking History", href: "#history" },
];

const bookingQuantity = reactive({});

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
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

const cancelBookingScaffold = (bookingId) => {
    router.patch(route("bookings.cancel", bookingId));
};

const syncActiveSidebar = () => {
    const hash = window.location.hash;
    if (hash === "#events") {
        activeSidebarKey.value = "events";
        return;
    }

    if (hash === "#history") {
        activeSidebarKey.value = "history";
        return;
    }

    activeSidebarKey.value = "overview";
};

onMounted(() => {
    syncActiveSidebar();
    window.addEventListener("hashchange", syncActiveSidebar);
});

onUnmounted(() => {
    window.removeEventListener("hashchange", syncActiveSidebar);
});
</script>

<template>
    <Head :title="`Dashboard | ${appName}`" />

    <div class="relative overflow-hidden bg-slate-950 text-white">
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_18%_20%,rgba(6,182,212,0.2),transparent_40%),radial-gradient(circle_at_82%_12%,rgba(249,115,22,0.2),transparent_35%),radial-gradient(circle_at_55%_100%,rgba(16,185,129,0.16),transparent_45%)]"
        />

        <Navbar title="Booking Dashboard" />
        <div
            id="top"
            class="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"
        >
            <div class="grid gap-6 lg:grid-cols-[260px,1fr]">
                <Sidebar
                    :app-name="appName"
                    :links="sidebarLinks"
                    :active-key="activeSidebarKey"
                />

                <main>
                    <section
                        class="mb-5 rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur"
                    >
                        <h1 class="text-xl font-bold md:text-2xl">
                            Booking Dashboard
                        </h1>
                        <p class="mt-1 text-sm text-slate-300">
                            View events, check seat limits, book tickets, and
                            manage your booking history.
                        </p>
                    </section>

                    <section class="mb-8 grid gap-4 sm:grid-cols-3">
                        <article
                            class="rounded-xl border border-white/10 bg-white/5 p-5"
                        >
                            <p
                                class="text-xs uppercase tracking-[0.15em] text-slate-300"
                            >
                                Total Events
                            </p>
                            <p class="mt-2 text-3xl font-black text-cyan-300">
                                {{ totals.events }}
                            </p>
                        </article>
                        <article
                            class="rounded-xl border border-white/10 bg-white/5 p-5"
                        >
                            <p
                                class="text-xs uppercase tracking-[0.15em] text-slate-300"
                            >
                                Booking History
                            </p>
                            <p class="mt-2 text-3xl font-black text-cyan-300">
                                {{ totals.bookingHistory }}
                            </p>
                        </article>
                        <article
                            class="rounded-xl border border-white/10 bg-white/5 p-5"
                        >
                            <p
                                class="text-xs uppercase tracking-[0.15em] text-slate-300"
                            >
                                Confirmed Bookings
                            </p>
                            <p class="mt-2 text-3xl font-black text-cyan-300">
                                {{ totals.confirmedBookings }}
                            </p>
                        </article>
                    </section>

                    <section
                        id="events"
                        class="mb-8 rounded-2xl border border-white/10 bg-white/5 p-6"
                    >
                        <h2 class="text-xl font-bold">Events</h2>
                        <p class="mt-2 text-sm text-slate-300">
                            Browse available events and use the booking scaffold
                            buttons while you build manual CRUD logic.
                        </p>

                        <div
                            v-if="events.length"
                            class="mt-5 grid gap-4 md:grid-cols-2"
                        >
                            <article
                                v-for="event in events"
                                :key="event.id"
                                class="rounded-xl border border-white/10 bg-slate-900/60 p-5"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div>
                                        <h3
                                            class="text-lg font-bold text-white"
                                        >
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
                                    {{
                                        event.description ||
                                        "No description provided yet."
                                    }}
                                </p>

                                <div
                                    class="mt-4 flex items-center justify-between gap-4"
                                >
                                    <p
                                        class="text-lg font-black text-orange-300"
                                    >
                                        {{ formatCurrency(event.price) }}
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model.number="
                                                bookingQuantity[event.id]
                                            "
                                            type="number"
                                            min="1"
                                            class="w-20 rounded-lg border border-white/20 bg-slate-900 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400"
                                            placeholder="1"
                                        />
                                        <button
                                            type="button"
                                            @click="
                                                openBookingScaffold(event.id)
                                            "
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
                            class="mt-5 rounded-xl border border-dashed border-white/20 bg-slate-900/40 p-4 text-sm text-slate-300"
                        >
                            No events yet. Create events in Filament, then they
                            will appear here.
                        </p>
                    </section>

                    <section
                        id="history"
                        class="rounded-2xl border border-white/10 bg-white/5 p-6"
                    >
                        <h2 class="text-xl font-bold">Booking History</h2>
                        <p class="mt-2 text-sm text-slate-300">
                            Track your confirmed and cancelled bookings.
                        </p>

                        <div
                            v-if="bookings.length"
                            class="mt-5 overflow-x-auto"
                        >
                            <table
                                class="min-w-full border-separate border-spacing-y-2 text-sm"
                            >
                                <thead>
                                    <tr class="text-left text-slate-300">
                                        <th class="px-3 py-2">Event</th>
                                        <th class="px-3 py-2">Date</th>
                                        <th class="px-3 py-2">Quantity</th>
                                        <th class="px-3 py-2">Total</th>
                                        <th class="px-3 py-2">Status</th>
                                        <th class="px-3 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="booking in bookings"
                                        :key="booking.id"
                                        class="rounded-xl border border-white/10 bg-slate-900/60"
                                    >
                                        <td class="px-3 py-3">
                                            {{
                                                booking.event?.title || "Event"
                                            }}
                                        </td>
                                        <td class="px-3 py-3 text-slate-300">
                                            {{
                                                formatDate(
                                                    booking.event?.event_date,
                                                )
                                            }}
                                        </td>
                                        <td class="px-3 py-3">
                                            {{ booking.quantity }}
                                        </td>
                                        <td class="px-3 py-3">
                                            {{
                                                formatCurrency(
                                                    booking.total_price,
                                                )
                                            }}
                                        </td>
                                        <td class="px-3 py-3">
                                            <span
                                                class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                                :class="
                                                    booking.status ===
                                                    'confirmed'
                                                        ? 'bg-emerald-400/20 text-emerald-300'
                                                        : 'bg-rose-400/20 text-rose-300'
                                                "
                                            >
                                                {{ booking.status }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3">
                                            <button
                                                type="button"
                                                :disabled="
                                                    booking.status ===
                                                    'cancelled'
                                                "
                                                @click="
                                                    cancelBookingScaffold(
                                                        booking.id,
                                                    )
                                                "
                                                class="rounded-lg border border-white/20 px-3 py-1.5 text-xs font-semibold transition enabled:hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-40"
                                            >
                                                Cancel Booking
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <p
                            v-else
                            class="mt-5 rounded-xl border border-dashed border-white/20 bg-slate-900/40 p-4 text-sm text-slate-300"
                        >
                            No booking history yet.
                        </p>
                    </section>
                </main>
            </div>
        </div>
    </div>
</template>
