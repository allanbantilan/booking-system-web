<script setup lang="ts">
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import Button from "primevue/button";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import MetricCard from "@/Components/Dashboard/MetricCard.vue";
import StatusBadge from "@/Components/Dashboard/StatusBadge.vue";

type ReservationSummary = {
    id: number;
    status: string;
    total_price: string | number;
    scheduled_for?: string | null;
    created_at?: string | null;
    booking?: { id: number; title: string; location?: string; category?: string } | null;
};

const props = withDefaults(defineProps<{
    totals?: { bookings: number; bookingHistory: number; confirmedBookings: number };
    statusBreakdown?: { pending: number; confirmed: number; cancelled: number };
    upcomingReservation?: ReservationSummary | null;
    recentReservations?: ReservationSummary[];
}>(), {
    totals: () => ({ bookings: 0, bookingHistory: 0, confirmedBookings: 0 }),
    statusBreakdown: () => ({ pending: 0, confirmed: 0, cancelled: 0 }),
    upcomingReservation: null,
    recentReservations: () => [],
});

const page = usePage();
const firstName = computed(() => String((page.props as any).auth?.user?.name || "there").split(" ")[0]);
const routeUrl = (name: string, params?: unknown) => route(name, params);
const formatCurrency = (value: string | number) => new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
    maximumFractionDigits: 0,
}).format(Number(value || 0));
const formatDate = (value?: string | null) => value
    ? new Intl.DateTimeFormat("en-PH", { month: "short", day: "numeric", year: "numeric" }).format(new Date(value))
    : "Schedule to be confirmed";
</script>

<template>
    <DashboardLayout title="Dashboard">
        <section class="ledger-panel p-6 sm:p-7">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="mt-3 max-w-2xl font-display text-3xl font-bold leading-tight text-ledger-text sm:text-4xl">
                        Good to see you, {{ firstName }}.
                    </h2>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-ledger-muted">
                        Your bookings, upcoming plans, and payment activity are organized in one place.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="routeUrl('bookings.index')">
                        <Button label="Explore bookings" icon="pi pi-compass" class="!rounded-lg" />
                    </Link>
                    <Link :href="routeUrl('bookings.history')">
                        <Button label="View history" icon="pi pi-calendar-clock" severity="secondary" outlined class="!rounded-lg" />
                    </Link>
                </div>
            </div>
        </section>

        <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <MetricCard label="Available listings" :value="totals.bookings" icon="pi pi-compass" tone="cyan" />
            <MetricCard label="Confirmed" :value="statusBreakdown.confirmed" icon="pi pi-check-circle" tone="emerald" />
            <MetricCard label="Pending" :value="statusBreakdown.pending" icon="pi pi-clock" tone="orange" />
            <MetricCard label="Cancelled" :value="statusBreakdown.cancelled" icon="pi pi-times-circle" tone="rose" />
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-[0.9fr_1.4fr]">
            <article class="ledger-panel p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold text-ledger-muted">Next up</p>
                        <h3 class="mt-2 font-display text-xl font-bold text-ledger-text">Upcoming booking</h3>
                    </div>
                    <span class="grid size-11 place-items-center rounded-lg border border-ledger-border bg-ledger-bg text-ledger-primary">
                        <i class="pi pi-calendar text-lg" />
                    </span>
                </div>

                <div v-if="upcomingReservation" class="mt-7">
                    <StatusBadge :status="upcomingReservation.status" />
                    <h4 class="mt-4 font-display text-2xl font-bold text-ledger-text">{{ upcomingReservation.booking?.title }}</h4>
                    <p class="mt-2 text-sm text-ledger-muted">
                        <i class="pi pi-map-marker mr-2 text-ledger-primary" />{{ upcomingReservation.booking?.location || "Location to be confirmed" }}
                    </p>
                    <p class="mt-2 text-sm text-ledger-muted">
                        <i class="pi pi-calendar mr-2 text-orange-400" />{{ formatDate(upcomingReservation.scheduled_for) }}
                    </p>
                    <Link v-if="upcomingReservation.booking?.id" :href="routeUrl('bookings.show', upcomingReservation.booking.id)" class="mt-6 inline-block">
                        <Button label="View booking" icon="pi pi-arrow-right" icon-pos="right" text />
                    </Link>
                </div>
                <div v-else class="mt-7 rounded-lg border border-dashed border-ledger-border bg-ledger-elevated p-6 text-center">
                    <i class="pi pi-calendar-plus text-2xl text-ledger-primary" />
                    <p class="mt-3 font-semibold text-ledger-text">No upcoming booking yet</p>
                    <p class="mt-1 text-sm text-ledger-muted">Explore available listings when you are ready.</p>
                </div>
            </article>

            <article class="ledger-panel p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-ledger-muted">Activity</p>
                        <h3 class="mt-2 font-display text-xl font-bold text-ledger-text">Recent reservations</h3>
                    </div>
                    <Link :href="routeUrl('bookings.history')" class="text-sm font-bold text-ledger-primary hover:text-ledger-text">View all</Link>
                </div>

                <div v-if="recentReservations.length" class="mt-5 divide-y divide-ledger-border">
                    <component
                        :is="reservation.booking?.id ? Link : 'div'"
                        v-for="reservation in recentReservations"
                        :key="reservation.id"
                        :href="reservation.booking?.id ? routeUrl('bookings.show', reservation.booking.id) : undefined"
                        class="flex items-center gap-4 py-4 transition hover:bg-ledger-elevated"
                    >
                        <span class="grid size-10 shrink-0 place-items-center rounded-lg border border-ledger-border bg-ledger-elevated text-ledger-primary">
                            <i class="pi pi-ticket" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-ledger-text">{{ reservation.booking?.title || "Booking" }}</p>
                            <p class="mt-1 text-xs text-ledger-muted">{{ formatDate(reservation.scheduled_for || reservation.created_at) }}</p>
                        </div>
                        <div class="hidden text-right sm:block">
                            <StatusBadge :status="reservation.status" />
                            <p class="mt-1 text-xs font-semibold text-ledger-muted">{{ formatCurrency(reservation.total_price) }}</p>
                        </div>
                    </component>
                </div>
                <div v-else class="mt-6 rounded-lg border border-dashed border-ledger-border p-8 text-center text-sm text-ledger-muted">
                    Your recent reservations will appear here.
                </div>
            </article>
        </section>
    </DashboardLayout>
</template>
