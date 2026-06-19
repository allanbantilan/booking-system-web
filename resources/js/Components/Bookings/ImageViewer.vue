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
const direction = ref(1); // 1 = advancing, -1 = going back; drives slide direction

const imageCount = computed(() => props.images.length);
const currentImage = computed(() => props.images[currentIndex.value]);
const slideName = computed(() =>
    direction.value === 1 ? "iv-slide-next" : "iv-slide-prev",
);

const clampIndex = (index) => {
    if (!imageCount.value) return 0;

    return Math.min(Math.max(index, 0), imageCount.value - 1);
};

const resetZoom = () => {
    zoom.value = 1;
};

const setIndex = (index) => {
    currentIndex.value = clampIndex(index);
    resetZoom();
};

// Thumbnail jump: infer direction from where we're headed.
const goTo = (index) => {
    direction.value = clampIndex(index) >= currentIndex.value ? 1 : -1;
    setIndex(index);
};

const next = () => {
    if (!imageCount.value) return;

    direction.value = 1;
    setIndex((currentIndex.value + 1) % imageCount.value);
};

const previous = () => {
    if (!imageCount.value) return;

    direction.value = -1;
    setIndex((currentIndex.value - 1 + imageCount.value) % imageCount.value);
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
    <!-- Teleport to body: a transformed ancestor (.bf-page-in animation) would
         otherwise become the containing block for this fixed overlay, dropping it
         to the bottom of the page instead of covering the viewport. -->
    <Teleport to="body">
        <Transition name="iv-fade">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-4 py-6"
                role="dialog"
                aria-modal="true"
                :aria-label="`${title} gallery`"
                @click.self="close"
            >
                <Transition name="iv-panel">
                    <div
                        v-if="open"
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
                class="relative flex min-h-[18rem] flex-1 items-center justify-center overflow-hidden bg-ledger-elevated p-4"
            >
                <button
                    type="button"
                    class="absolute left-3 top-1/2 z-10 -translate-y-1/2 rounded-full border border-ledger-border bg-ledger-surface p-2.5 text-ledger-text transition hover:bg-ledger-bg"
                    aria-label="Previous image"
                    @click="previous"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15.75 19.5 8.25 12l7.5-7.5"
                        />
                    </svg>
                </button>
                <Transition :name="slideName">
                    <div
                        :key="currentIndex"
                        class="absolute inset-0 flex items-center justify-center p-4"
                    >
                        <img
                            v-if="currentImage"
                            :src="currentImage"
                            :alt="`${title} image ${currentIndex + 1}`"
                            class="max-h-[65vh] max-w-full object-contain transition-transform duration-150"
                            :style="{ transform: `scale(${zoom})` }"
                        />
                    </div>
                </Transition>
                <button
                    type="button"
                    class="absolute right-3 top-1/2 z-10 -translate-y-1/2 rounded-full border border-ledger-border bg-ledger-surface p-2.5 text-ledger-text transition hover:bg-ledger-bg"
                    aria-label="Next image"
                    @click="next"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m8.25 4.5 7.5 7.5-7.5 7.5"
                        />
                    </svg>
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
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
/* Open / close: backdrop fades, panel rises and settles. Exit is quicker. */
.iv-fade-enter-active,
.iv-fade-leave-active {
    transition: opacity 300ms var(--ease-out, cubic-bezier(0.22, 1, 0.36, 1));
}
.iv-fade-leave-active {
    transition-duration: 200ms;
}
.iv-fade-enter-from,
.iv-fade-leave-to {
    opacity: 0;
}

.iv-panel-enter-active {
    transition:
        opacity 300ms var(--ease-out, cubic-bezier(0.22, 1, 0.36, 1)),
        transform 300ms var(--ease-out, cubic-bezier(0.22, 1, 0.36, 1));
}
.iv-panel-leave-active {
    transition:
        opacity 200ms ease-in,
        transform 200ms ease-in;
}
.iv-panel-enter-from,
.iv-panel-leave-to {
    opacity: 0;
    transform: translateY(8px) scale(0.97);
}

/* Carousel: the incoming and outgoing frames overlap and slide in the travel
   direction. Transform lives on the wrapper so it never fights the img zoom. */
.iv-slide-next-enter-active,
.iv-slide-next-leave-active,
.iv-slide-prev-enter-active,
.iv-slide-prev-leave-active {
    transition:
        opacity 260ms var(--ease-out, cubic-bezier(0.22, 1, 0.36, 1)),
        transform 260ms var(--ease-out, cubic-bezier(0.22, 1, 0.36, 1));
}
.iv-slide-next-enter-from {
    opacity: 0;
    transform: translateX(28px);
}
.iv-slide-next-leave-to {
    opacity: 0;
    transform: translateX(-28px);
}
.iv-slide-prev-enter-from {
    opacity: 0;
    transform: translateX(-28px);
}
.iv-slide-prev-leave-to {
    opacity: 0;
    transform: translateX(28px);
}

@media (prefers-reduced-motion: reduce) {
    .iv-fade-enter-active,
    .iv-fade-leave-active,
    .iv-panel-enter-active,
    .iv-panel-leave-active,
    .iv-slide-next-enter-active,
    .iv-slide-next-leave-active,
    .iv-slide-prev-enter-active,
    .iv-slide-prev-leave-active {
        transition-duration: 0.01ms !important;
    }
    .iv-panel-enter-from,
    .iv-panel-leave-to,
    .iv-slide-next-enter-from,
    .iv-slide-next-leave-to,
    .iv-slide-prev-enter-from,
    .iv-slide-prev-leave-to {
        transform: none !important;
    }
}
</style>
