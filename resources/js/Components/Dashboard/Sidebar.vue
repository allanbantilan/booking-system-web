<script setup>
import { Link } from "@inertiajs/vue3";

const emit = defineEmits(["navigate"]);

defineProps({
    appName: {
        type: String,
        default: "BookFlow",
    },
    links: {
        type: Array,
        default: () => [],
    },
    activeKey: {
        type: String,
        default: "overview",
    },
});
</script>

<template>
    <aside class="h-fit rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
        <p class="mb-3 px-3 text-xs uppercase tracking-[0.2em] text-cyan-300">{{ appName }}</p>
        <nav class="space-y-2">
            <Link
                v-for="link in links"
                :key="link.label"
                :href="link.href"
                @click="emit('navigate')"
                :class="[
                    'block rounded-lg px-4 py-2 font-medium transition-colors',
                    link.key === activeKey
                        ? 'bg-white/10 text-cyan-300 border border-white/20'
                        : 'text-slate-200 hover:text-cyan-300',
                ]"
            >
                {{ link.label }}
            </Link>
        </nav>
    </aside>
</template>
