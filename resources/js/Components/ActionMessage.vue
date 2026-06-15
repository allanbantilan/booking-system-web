<script setup>
defineProps({
    on: Boolean,
    variant: {
        type: String,
        default: 'light',
    },
});
</script>

<template>
    <div>
        <transition leave-active-class="transition ease-in duration-1000" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div
                v-show="on"
                class="text-sm"
                :class="variant === 'dashboard' ? 'text-ledger-muted' : 'text-ledger-muted'"
            >
                <slot />
            </div>
        </transition>
    </div>
</template>
