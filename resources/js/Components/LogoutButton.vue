<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { ref } from "vue";
import Button from "primevue/button";
import Dialog from "primevue/dialog";

withDefaults(defineProps<{
    iconOnly?: boolean;
    label?: string;
}>(), {
    iconOnly: false,
    label: "Log out",
});

const confirming = ref(false);
const processing = ref(false);

const logout = () => {
    processing.value = true;

    router.post(route("logout"), {}, {
        onFinish: () => {
            processing.value = false;
            confirming.value = false;
        },
    });
};
</script>

<template>
    <slot :open="() => confirming = true">
        <Button
            :label="iconOnly ? undefined : label"
            icon="pi pi-sign-out"
            severity="danger"
            :text="iconOnly"
            :rounded="iconOnly"
            :aria-label="label"
            @click="confirming = true"
        />
    </slot>

    <Dialog
        v-model:visible="confirming"
        modal
        header="Confirm logout"
        :draggable="false"
        :style="{ width: 'min(28rem, calc(100vw - 2rem))' }"
    >
        <div class="flex gap-4">
            <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-orange-500/15 text-orange-400">
                <i class="pi pi-sign-out" />
            </span>
            <div>
                <p class="font-semibold text-ledger-text">Are you sure you want to log out?</p>
                <p class="mt-1 text-sm leading-6 text-ledger-muted">
                    You will need to sign in again to access your booking workspace.
                </p>
            </div>
        </div>

        <template #footer>
            <Button label="Stay signed in" severity="secondary" text @click="confirming = false" />
            <Button label="Log out" icon="pi pi-sign-out" severity="danger" :loading="processing" @click="logout" />
        </template>
    </Dialog>
</template>
