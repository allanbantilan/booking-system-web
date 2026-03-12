<script setup>
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import UpdatePasswordForm from "@/Pages/Profile/Partials/UpdatePasswordForm.vue";
import UpdateProfileInformationForm from "@/Pages/Profile/Partials/UpdateProfileInformationForm.vue";
import { router } from "@inertiajs/vue3";

defineProps({
    confirmsTwoFactorAuthentication: Boolean,
    sessions: Array,
});

const logout = () => {
    router.post(route("logout"));
};
</script>

<template>
    <DashboardLayout title="User Settings">
        <section
            class="mb-5 rounded-xl border border-white/10 bg-white/5 p-4 backdrop-blur"
        >
            <h1 class="text-xl font-bold md:text-2xl">User Settings</h1>
            <p class="mt-1 text-sm text-slate-300">
                Update your profile details, change your password, or sign out.
            </p>
        </section>

        <div class="space-y-6">
            <section
                id="profile-info"
                class="rounded-2xl border border-white/10 bg-white/5 p-6"
            >
                <UpdateProfileInformationForm :user="$page.props.auth.user" />
            </section>

            <section
                id="change-password"
                class="rounded-2xl border border-white/10 bg-white/5 p-6"
            >
                <UpdatePasswordForm />
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/5 p-6">
                <h2 class="text-lg font-semibold text-white">Log Out</h2>
                <p class="mt-2 text-sm text-slate-300">
                    Use this button to end your current session.
                </p>
                <button
                    type="button"
                    class="mt-4 rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10"
                    @click="logout"
                >
                    Log Out
                </button>
            </section>
        </div>
    </DashboardLayout>
</template>
