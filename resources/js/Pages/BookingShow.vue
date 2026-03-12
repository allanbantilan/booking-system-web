<script setup>
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { router } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    booking: {
        type: Object,
        required: true,
    },
});

const quantity = ref(1);

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

const getDiscountPercentage = () =>
    Number(props.booking.discount_percentage || 0);

const getOriginalPrice = () => {
    const discount = getDiscountPercentage();
    return props.booking.price * (1 + discount / 100);
};

const primaryImage = computed(() => props.booking.image_urls?.[0]);

const reserveBooking = () => {
    router.post(route("reservations.store", props.booking.id), {
        quantity: Number(quantity.value || 1),
    });
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
                {{ booking.location }} - {{ formatDate(booking.event_date) }}
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
                            {{ booking.availability_label || "Slots left" }}:
                            {{ booking.capacity }}
                        </span>
                    </div>
                </div>
            </section>

            <aside class="rounded-2xl border border-white/10 bg-white/5 p-6">
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">
                    Booking Info
                </p>
                <div class="mt-4 space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-sm text-slate-300">Price</span>
                        <div class="text-right">
                            <div class="text-lg font-black text-orange-300">
                                {{ formatCurrency(booking.price) }}
                            </div>
                            <div v-if="getDiscountPercentage() > 0" class="text-xs text-slate-400 line-through">
                                {{ formatCurrency(getOriginalPrice()) }}
                            </div>
                            <div v-if="getDiscountPercentage() > 0" class="text-[10px] uppercase tracking-[0.2em] text-emerald-300">
                                -{{ getDiscountPercentage() }}%
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-300">Created By</span>
                        <span class="text-sm text-white">
                            {{ booking.creator?.name || "Admin" }}
                        </span>
                    </div>
                </div>

                <div class="mt-6">
                    <label class="text-sm text-slate-300">Quantity</label>
                    <input
                        v-model.number="quantity"
                        type="number"
                        min="1"
                        class="mt-2 w-full rounded-lg border border-white/20 bg-slate-900 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400"
                    />
                    <button
                        type="button"
                        @click="reserveBooking"
                        class="mt-4 w-full rounded-lg bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-400"
                    >
                        Reserve Now
                    </button>
                </div>
            </aside>
        </div>
    </DashboardLayout>
</template>


