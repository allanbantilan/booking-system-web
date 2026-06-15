<script setup>
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import UpdatePasswordForm from "@/Pages/Profile/Partials/UpdatePasswordForm.vue";
import UpdateProfileInformationForm from "@/Pages/Profile/Partials/UpdateProfileInformationForm.vue";
import ActionMessage from "@/Components/ActionMessage.vue";
import LogoutButton from "@/Components/LogoutButton.vue";
import { useForm, useRemember } from "@inertiajs/vue3";

defineProps({
    confirmsTwoFactorAuthentication: Boolean,
    sessions: Array,
});

const merchantForm = useForm({
    message: "",
});
const merchantNotice = useRemember("", "merchant-notice");

const requestMerchantAccess = () => {
    merchantForm.post(route("merchant-account.store"), {
        preserveScroll: true,
        onSuccess: () => {
            merchantNotice.value =
                "Request sent. We will review your request and email you if approved.";
            merchantForm.reset("message");
        },
    });
};
</script>

<template>
    <DashboardLayout title="User Settings">
        <section
            class="mb-5 rounded-xl border border-ledger-border bg-ledger-surface p-4 backdrop-blur"
        >
            <h1 class="text-xl font-bold md:text-2xl">User Settings</h1>
            <p class="mt-1 text-sm text-ledger-muted">
                Update your profile details, change your password, or sign out.
            </p>
        </section>

        <div class="space-y-6">
            <section
                id="profile-info"
                class="rounded-2xl border border-ledger-border bg-ledger-surface p-6"
            >
                <UpdateProfileInformationForm :user="$page.props.auth.user" />
            </section>

            <section
                id="change-password"
                class="rounded-2xl border border-ledger-border bg-ledger-surface p-6"
            >
                <UpdatePasswordForm />
            </section>

            <section class="rounded-2xl border border-ledger-border bg-ledger-surface p-6">
                <h2 class="text-lg font-semibold text-ledger-text">Be a Merchant</h2>
                <p class="mt-2 text-sm text-ledger-muted">
                    Have a place to rent? We’ll create backend credentials for you and send them to your email.
                </p>
                <div class="mt-4">
                    <label class="text-sm font-medium text-ledger-text">
                        Tell us about your place
                    </label>
                    <textarea
                        v-model="merchantForm.message"
                        rows="4"
                        class="mt-2 w-full rounded-lg border border-ledger-border bg-ledger-surface p-3 text-sm text-ledger-text placeholder:text-ledger-muted focus:border-cyan-400 focus:outline-none focus:ring-1 focus:ring-cyan-400"
                        placeholder="Share a short description, location, and capacity."
                    ></textarea>
                    <p v-if="merchantForm.errors.message" class="mt-2 text-sm text-rose-300">
                        {{ merchantForm.errors.message }}
                    </p>
                </div>
                <p v-if="merchantNotice" class="mt-3 text-sm text-emerald-300">
                    {{ merchantNotice }}
                </p>
                <p v-else-if="$page.props.flash?.success" class="mt-3 text-sm text-emerald-300">
                    {{ $page.props.flash.success }}
                </p>
                <p v-else-if="$page.props.flash?.error" class="mt-3 text-sm text-rose-300">
                    {{ $page.props.flash.error }}
                </p>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <ActionMessage :on="merchantForm.recentlySuccessful" class="text-emerald-300">
                        Request sent.
                    </ActionMessage>
                    <button
                        type="button"
                        class="rounded-lg border border-ledger-border px-4 py-2 text-sm font-semibold text-ledger-text transition hover:bg-ledger-elevated disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="merchantForm.processing"
                        @click="requestMerchantAccess"
                    >
                        Request Merchant Access
                    </button>
                </div>
            </section>

            <section class="rounded-2xl border border-ledger-border bg-ledger-surface p-6">
                <h2 class="text-lg font-semibold text-ledger-text">Log Out</h2>
                <p class="mt-2 text-sm text-ledger-muted">
                    Use this button to end your current session.
                </p>
                <div class="mt-4">
                    <LogoutButton label="Log Out" />
                </div>
            </section>
        </div>
    </DashboardLayout>
</template>
