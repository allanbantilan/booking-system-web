<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import Dropdown from "@/Components/Dropdown.vue";
import LogoutButton from "@/Components/LogoutButton.vue";

defineProps({
    title: {
        type: String,
        default: "Dashboard",
    },
});

const page = usePage();
const appName = computed(() => page.props.appName || "BookBound");
const userName = computed(() => page.props.auth?.user?.name || "User");
const userEmail = computed(() => page.props.auth?.user?.email || "");

</script>

<template>
    <nav class="w-full z-50 border-b border-ledger-border bg-ledger-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
            <div class="grid grid-cols-2 gap-4 lg:h-16 lg:grid-cols-3 lg:items-center">
                <div class="order-1 col-span-1 flex w-full items-center gap-3">
                    <Link
                        :href="route('dashboard')"
                        class="flex items-center space-x-2 group"
                    >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg border border-ledger-border bg-ledger-surface text-ledger-primary"
                        >
                            <svg
                                class="h-6 w-6"
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
                            <span class="text-xl font-bold text-ledger-text">{{
                                appName
                            }}</span>
                            <p class="text-xs leading-tight text-ledger-muted">
                                {{ title }}
                            </p>
                        </div>
                    </Link>
                </div>

                <div class="order-3 col-span-2 flex w-full items-center justify-center lg:order-2 lg:col-span-1 mb-4">
                    <nav
                        class="no-scrollbar flex flex-nowrap items-center justify-start gap-2 overflow-x-auto rounded-full border border-white/10 bg-white/5 px-2 py-1 text-xs text-slate-200 sm:justify-center sm:text-sm"
                    >
                        <Link
                            :href="route('dashboard')"
                            class="whitespace-nowrap rounded-full px-3 py-1.5 font-semibold transition"
                            :class="
                                route().current('dashboard')
                                    ? 'bg-white/10 text-cyan-300'
                                    : 'text-slate-200 hover:text-white hover:bg-white/5'
                            "
                        >
                            Dashboard
                        </Link>
                        <Link
                            :href="route('bookings.index')"
                            class="whitespace-nowrap rounded-full px-3 py-1.5 font-semibold transition"
                            :class="
                                route().current('bookings.index') || route().current('bookings.show')
                                    ? 'bg-white/10 text-cyan-300'
                                    : 'text-slate-200 hover:text-white hover:bg-white/5'
                            "
                        >
                            Available Bookings
                        </Link>
                        <Link
                            :href="route('bookings.history')"
                            class="whitespace-nowrap rounded-full px-3 py-1.5 font-semibold transition"
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

                <div class="order-2 col-span-1 flex w-full items-center justify-end space-x-3 lg:order-3">
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

                            <div class="px-2 py-1">
                                <LogoutButton label="Log Out" />
                            </div>
                        </template>
                    </Dropdown>
                </div>
            </div>
        </div>
    </nav>
</template>
