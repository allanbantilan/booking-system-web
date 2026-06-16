<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    images: {
        type: Array,
        default: () => [],
    },
    title: {
        type: String,
        default: "Booking image",
    },
    initialIndex: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(["close"]);

const currentIndex = ref(0);
const zoom = ref(1);

const imageCount = computed(() => props.images.length);
const currentImage = computed(() => props.images[currentIndex.value]);

const clampIndex = (index) => {
    if (!imageCount.value) return 0;

    return Math.min(Math.max(index, 0), imageCount.value - 1);
};

const resetZoom = () => {
    zoom.value = 1;
};

const goTo = (index) => {
    currentIndex.value = clampIndex(index);
    resetZoom();
};

const next = () => {
    if (!imageCount.value) return;

    goTo((currentIndex.value + 1) % imageCount.value);
};

const previous = () => {
    if (!imageCount.value) return;

    goTo((currentIndex.value - 1 + imageCount.value) % imageCount.value);
};

const zoomIn = () => {
    zoom.value = Math.min(3, Number((zoom.value + 0.25).toFixed(2)));
};

const zoomOut = () => {
    zoom.value = Math.max(1, Number((zoom.value - 0.25).toFixed(2)));
};

const close = () => {
    emit("close");
};

const handleKeydown = (event) => {
    if (!props.open) return;

    if (event.key === "Escape") close();
    if (event.key === "ArrowRight") next();
    if (event.key === "ArrowLeft") previous();
};

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        currentIndex.value = clampIndex(props.initialIndex);
        resetZoom();
    },
);

onMounted(() => window.addEventListener("keydown", handleKeydown));
onBeforeUnmount(() => window.removeEventListener("keydown", handleKeydown));
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-4 py-6"
        role="dialog"
        aria-modal="true"
        :aria-label="`${title} gallery`"
        @click.self="close"
    >
        <div
            class="flex max-h-full w-full max-w-5xl flex-col rounded-2xl border border-ledger-border bg-ledger-surface shadow-xl"
        >
            <div
                class="flex items-center justify-between gap-3 border-b border-ledger-border px-4 py-3"
            >
                <div>
                    <h2 class="text-base font-semibold text-ledger-text">
                        {{ title }}
                    </h2>
                    <p class="text-xs text-ledger-muted">
                        {{ currentIndex + 1 }} of {{ imageCount }}
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-lg border border-ledger-border px-3 py-1.5 text-sm text-ledger-text transition hover:bg-ledger-elevated"
                    @click="close"
                >
                    Close
                </button>
            </div>

            <div
                class="relative flex min-h-[18rem] flex-1 items-center justify-center overflow-auto bg-ledger-elevated p-4"
            >
                <button
                    type="button"
                    class="absolute left-3 top-1/2 z-10 rounded-full border border-ledger-border bg-ledger-surface px-3 py-2 text-ledger-text transition hover:bg-ledger-bg"
                    @click="previous"
                >
                    Prev
                </button>
                <img
                    v-if="currentImage"
                    :src="currentImage"
                    :alt="`${title} image ${currentIndex + 1}`"
                    class="max-h-[65vh] max-w-full object-contain transition-transform duration-150"
                    :style="{ transform: `scale(${zoom})` }"
                />
                <button
                    type="button"
                    class="absolute right-3 top-1/2 z-10 rounded-full border border-ledger-border bg-ledger-surface px-3 py-2 text-ledger-text transition hover:bg-ledger-bg"
                    @click="next"
                >
                    Next
                </button>
            </div>

            <div
                class="flex flex-wrap items-center justify-between gap-3 border-t border-ledger-border px-4 py-3"
            >
                <div class="flex gap-2 overflow-x-auto">
                    <button
                        v-for="(image, index) in images"
                        :key="`${image}-${index}`"
                        type="button"
                        class="h-14 w-20 overflow-hidden rounded-lg border"
                        :class="
                            index === currentIndex
                                ? 'border-cyan-400'
                                : 'border-ledger-border'
                        "
                        @click="goTo(index)"
                    >
                        <img
                            :src="image"
                            :alt="`${title} thumbnail ${index + 1}`"
                            class="h-full w-full object-cover"
                        />
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-ledger-border px-3 py-1.5 text-sm text-ledger-text transition hover:bg-ledger-elevated"
                        @click="zoomOut"
                    >
                        -
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-ledger-border px-3 py-1.5 text-sm text-ledger-text transition hover:bg-ledger-elevated"
                        @click="resetZoom"
                    >
                        {{ Math.round(zoom * 100) }}%
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-ledger-border px-3 py-1.5 text-sm text-ledger-text transition hover:bg-ledger-elevated"
                        @click="zoomIn"
                    >
                        +
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
