<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import Dropdown from "@/Components/Dropdown.vue";

defineProps({
    title: {
        type: String,
        default: "Dashboard",
    },
});

const page = usePage();
const appName = computed(() => page.props.appName || "BookFlow");
const userName = computed(() => page.props.auth?.user?.name || "User");
const userEmail = computed(() => page.props.auth?.user?.email || "");

const logout = () => {
    router.post(route("logout"));
};
</script>

<template>
    <nav class="w-full z-50 bg-transparent border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
            <div class="flex items-center h-16">
                <div class="flex w-1/3 items-center gap-3">
                    <Link
                        :href="route('dashboard')"
                        class="flex items-center space-x-2 group"
                    >
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
                            <span class="text-xl font-bold text-white">{{
                                appName
                            }}</span>
                            <p class="text-xs text-slate-300 leading-tight">
                                {{ title }}
                            </p>
                        </div>
                    </Link>
                </div>

                <div class="flex w-1/3 items-center justify-center">
                    <nav
                        class="flex items-center gap-2 overflow-x-auto whitespace-nowrap rounded-full border border-white/10 bg-white/5 px-2 py-1 text-sm text-slate-200"
                    >
                        <Link
                            :href="route('dashboard')"
                            class="rounded-full px-3 py-1.5 font-semibold transition"
                            :class="
                                route().current('dashboard')
                                    ? 'bg-white/10 text-cyan-300'
                                    : 'text-slate-200 hover:text-white hover:bg-white/5'
                            "
                        >
                            Dashboard
                        </Link>
                        <Link
                            :href="route('events.index')"
                            class="rounded-full px-3 py-1.5 font-semibold transition"
                            :class="
                                route().current('events.index')
                                    ? 'bg-white/10 text-cyan-300'
                                    : 'text-slate-200 hover:text-white hover:bg-white/5'
                            "
                        >
                            Events
                        </Link>
                        <Link
                            :href="route('bookings.history')"
                            class="rounded-full px-3 py-1.5 font-semibold transition"
                            :class="
                                route().current('bookings.history')
                                    ? 'bg-white/10 text-cyan-300'
                                    : 'text-slate-200 hover:text-white hover:bg-white/5'
                            "
                        >
                            Booking History
                        </Link>
                    </nav>
                </div>

                <div class="flex w-1/3 items-center justify-end space-x-3">
                    <Dropdown
                        align="right"
                        width="64"
                        :content-classes="[
                            'py-2',
                            'bg-slate-900/95',
                            'border',
                            'border-white/10',
                            'backdrop-blur',
                        ]"
                    >
                        <template #trigger>
                            <button
                                type="button"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/10 bg-slate-900/70 text-white shadow-sm transition hover:bg-white/10"
                                aria-label="User settings"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <!-- Head -->
                                    <circle
                                        cx="12"
                                        cy="8"
                                        r="4"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                    <!-- Shoulders -->
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M4 20c0-4 3.582-7 8-7s8 3 8 7"
                                    />
                                </svg>
                            </button>
                        </template>

                        <template #content>
                            <div class="px-4 pb-3 pt-2 text-sm">
                                <p class="font-semibold text-white">
                                    {{ userName }}
                                </p>
                                <p class="text-xs text-slate-300">
                                    {{ userEmail }}
                                </p>
                            </div>

                            <div class="border-t border-white/10"></div>

                            <div class="py-1 text-sm">
                                <Link
                                    :href="`${route('profile.show')}#profile-info`"
                                    class="block px-4 py-2 text-slate-200 transition hover:bg-white/10"
                                >
                                    User Info
                                </Link>
                                <Link
                                    :href="`${route('profile.show')}#change-password`"
                                    class="block px-4 py-2 text-slate-200 transition hover:bg-white/10"
                                >
                                    Change Password
                                </Link>
                            </div>

                            <div class="border-t border-white/10"></div>

                            <form @submit.prevent="logout">
                                <button
                                    type="submit"
                                    class="block w-full px-4 py-2 text-left text-sm font-semibold text-rose-200 transition hover:bg-white/10"
                                >
                                    Log Out
                                </button>
                            </form>
                        </template>
                    </Dropdown>
                </div>
            </div>
        </div>
    </nav>
</template>
