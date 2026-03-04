<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

defineProps({
    title: {
        type: String,
        default: "Dashboard",
    },
});

const page = usePage();
const appName = computed(() => page.props.appName || "BookFlow");
const userName = computed(() => page.props.auth?.user?.name || "User");

const logout = () => {
    router.post(route("logout"));
};
</script>

<template>
    <nav class="w-full z-50 bg-transparent border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <Link href="/" class="flex items-center space-x-2 group">
                        <div
                            class="w-10 h-10 bg-cyan-500 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-shadow"
                        >
                            <svg
                                class="w-6 h-6 text-white"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"
                                />
                            </svg>
                        </div>
                        <div>
                            <span class="text-xl font-bold text-white">{{ appName }}</span>
                            <p class="text-xs text-slate-300 leading-tight">{{ title }}</p>
                        </div>
                    </Link>
                </div>

                <div class="hidden md:flex items-center space-x-4">
                    <span class="text-slate-200 font-medium">{{ userName }}</span>
                    <button
                        type="button"
                        @click="logout"
                        class="bg-cyan-500 hover:bg-cyan-400 text-slate-950 px-6 py-2 rounded-lg font-semibold shadow-md hover:shadow-lg transition-all duration-200"
                    >
                        Log Out
                    </button>
                </div>
            </div>
        </div>
    </nav>
</template>
