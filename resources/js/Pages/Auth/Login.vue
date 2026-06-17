<script setup>
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import Navigation from "@/Components/Home/Navigation.vue";

const props = defineProps({
    canResetPassword: Boolean,
    status: String,
});

const page = usePage();
const appName = computed(() => page.props.appName || "BookBound");
const showPassword = ref(false);

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        remember: form.remember ? "on" : "",
    })).post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head :title="`Sign In | ${appName}`" />

    <div class="relative min-h-screen bg-ledger-bg">
        <Navigation
            :can-login="false"
            :can-register="false"
            :app-name="appName"
            :hide-main-links="true"
        />

        <div
            class="relative mx-auto grid min-h-screen max-w-6xl items-center gap-10 px-4 pb-10 pt-24 sm:px-6 lg:grid-cols-2 lg:px-8"
        >
            <section class="hidden text-ledger-text lg:block">
                <p class="bf-kicker mb-5">{{ appName }}</p>
                <h1 class="font-display text-5xl font-bold leading-tight">
                    Welcome back to your bookings.
                </h1>
                <p class="mt-5 max-w-md text-base text-ledger-text/85">
                    Sign in to view your calendar, reservations, and customer activity.
                </p>
            </section>

            <section
                class="ledger-panel p-6 sm:p-8"
            >
                <h2 class="text-3xl font-black tracking-tight text-ledger-text">
                    Sign in
                </h2>
                <p class="mt-2 text-sm text-ledger-muted">
                    View bookings, payments, and receipts.
                </p>

                <div
                    v-if="status"
                    class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
                >
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="mt-6 space-y-5">
                    <div class="relative">
                        <label
                            for="email"
                            class="mb-1 block text-sm font-semibold text-ledger-text"
                        >
                            Email
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autofocus
                            autocomplete="username"
                            class="bf-field block w-full px-4 py-3 placeholder:text-ledger-muted outline-none"
                            placeholder="you@company.com"
                        />
                        <p
                            v-if="form.errors.email"
                            class="mt-1 text-sm text-rose-600"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <label
                                for="password"
                                class="block text-sm font-semibold text-ledger-text"
                            >
                                Password
                            </label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-sm font-medium text-ledger-primary hover:text-ledger-text"
                            >
                                Forgot password?
                            </Link>
                        </div>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                class="bf-field block w-full px-4 py-3 pr-14 placeholder:text-ledger-muted outline-none"
                                placeholder="Your password"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-2 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-lg text-ledger-muted transition hover:bg-ledger-elevated hover:text-ledger-text focus-visible:ring-2 focus-visible:ring-cyan-400"
                                :aria-label="
                                    showPassword
                                        ? 'Hide password'
                                        : 'Show password'
                                "
                            >
                                <svg
                                    v-if="showPassword"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    class="h-5 w-5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M3 3l18 18M10.58 10.58A2 2 0 0013.42 13.42M9.88 5.09A10.94 10.94 0 0112 4.89c5.05 0 8.27 4.22 9 6.11a11.8 11.8 0 01-3.08 4.35M6.61 6.61C4.7 8 3.51 9.87 3 11c.73 1.89 3.95 6.11 9 6.11 1.53 0 2.89-.39 4.09-1.01"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    class="h-5 w-5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M2.46 12C3.73 8.9 7.15 6 12 6s8.27 2.9 9.54 6c-1.27 3.1-4.69 6-9.54 6s-8.27-2.9-9.54-6z"
                                    />
                                    <circle
                                        cx="12"
                                        cy="12"
                                        r="3"
                                        stroke-width="1.8"
                                    />
                                </svg>
                            </button>
                        </div>
                        <p
                            v-if="form.errors.password"
                            class="mt-1 text-sm text-rose-600"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <label
                        class="inline-flex cursor-pointer items-center gap-2 text-sm text-ledger-muted"
                    >
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            name="remember"
                            class="h-4 w-4 rounded border-ledger-border text-cyan-600 focus:ring-cyan-500"
                        />
                        Remember me
                    </label>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bf-button bf-button-primary w-full disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        {{ form.processing ? "Signing in..." : "Sign in" }}
                    </button>

                    <p class="text-center text-sm text-ledger-muted">
                        New to {{ appName }}?
                        <Link
                            :href="route('register')"
                            class="font-semibold text-ledger-primary hover:text-ledger-text"
                        >
                            Create account
                        </Link>
                    </p>
                </form>
            </section>
        </div>
    </div>
</template>
