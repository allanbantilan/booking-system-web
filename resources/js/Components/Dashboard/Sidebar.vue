<script setup lang="ts">
import { Link, usePage } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, ref, watch } from "vue";
import Button from "primevue/button";
import LogoutButton from "@/Components/LogoutButton.vue";
import BrandMark from "@/Components/BrandMark.vue";

const props = defineProps<{
    collapsed?: boolean;
    mobile?: boolean;
}>();

const emit = defineEmits<{
    navigate: [];
    toggle: [];
}>();

const page = usePage();
const appName = computed(() => String(page.props.appName || "BookBound"));
const user = computed(() => (page.props as any).auth?.user as { name?: string; email?: string } | undefined);
const routeUrl = (name: string) => route(name);
const showExpandedContent = ref(!props.collapsed || props.mobile);
let revealTimer: number | undefined;

const links = [
    { label: "Dashboard", route: "dashboard", icon: "pi pi-chart-bar", matches: ["dashboard"] },
    { label: "Available Bookings", route: "bookings.index", icon: "pi pi-compass", matches: ["bookings.index", "bookings.show"] },
    { label: "Booking History", route: "bookings.history", icon: "pi pi-calendar-clock", matches: ["bookings.history"] },
    { label: "Settings", route: "profile.show", icon: "pi pi-cog", matches: ["profile.show"] },
];

const isActive = (matches: string[]) => matches.some((name) => route().current(name));
const initials = computed(() =>
    (user.value?.name || "User")
        .split(" ")
        .map((part) => part[0])
        .join("")
        .slice(0, 2)
        .toUpperCase(),
);

watch(
    () => [props.collapsed, props.mobile],
    ([collapsed, mobile]) => {
        window.clearTimeout(revealTimer);

        if (mobile || !collapsed) {
            revealTimer = window.setTimeout(() => {
                showExpandedContent.value = true;
            }, mobile ? 0 : 170);
            return;
        }

        showExpandedContent.value = false;
    },
);

onBeforeUnmount(() => window.clearTimeout(revealTimer));
</script>

<template>
    <aside
        class="flex h-full flex-col overflow-hidden border-r border-ledger-border bg-ledger-surface px-3 py-4 transition-[width] duration-200"
        :class="collapsed && !mobile ? 'w-20' : 'w-72'"
    >
        <div
            class="relative h-20 px-2"
        >
            <Link
                :href="routeUrl('dashboard')"
                class="flex min-w-0 items-center gap-3"
                :class="collapsed && !mobile ? 'w-full justify-center' : 'w-full pr-12'"
                @click="emit('navigate')"
            >
                <BrandMark class="size-10 shrink-0 text-ledger-text" />
                <span v-if="showExpandedContent" class="min-w-0">
                    <strong class="block truncate font-display text-lg text-ledger-text">{{ appName }}</strong>
                    <span class="block text-xs font-semibold text-ledger-muted">Bookings and payments</span>
                </span>
            </Link>

            <Button
                v-if="!mobile"
                text
                rounded
                severity="secondary"
                :icon="collapsed ? 'pi pi-angle-right' : 'pi pi-angle-left'"
                :class="collapsed ? '!absolute !left-1/2 !top-11 !size-9 !-translate-x-1/2' : '!absolute !right-2 !top-1 !size-9'"
                aria-label="Toggle sidebar"
                @click="emit('toggle')"
            />
        </div>

        <div class="mx-2 h-14 border-t border-ledger-border pt-4">
            <p
                class="px-3 text-xs font-semibold text-ledger-muted transition-opacity duration-150"
                :class="showExpandedContent ? 'opacity-100' : 'opacity-0'"
            >
                Menu
            </p>
        </div>

        <nav class="flex-1 space-y-1.5" aria-label="Main menu">
            <Link
                v-for="link in links"
                :key="link.route"
                :href="routeUrl(link.route)"
                :title="collapsed && !mobile ? link.label : undefined"
                class="group relative flex min-h-11 items-center rounded-lg text-sm font-semibold transition"
                :class="[
                    collapsed && !mobile ? 'mx-auto w-11 justify-center px-0' : 'w-full gap-3 px-3',
                    isActive(link.matches)
                        ? 'bg-ledger-elevated text-ledger-text'
                        : 'text-ledger-muted hover:bg-ledger-elevated hover:text-ledger-text',
                ]"
                @click="emit('navigate')"
            >
                <span
                    v-if="isActive(link.matches) && (!collapsed || mobile)"
                    class="absolute inset-y-2 left-0 w-0.5 rounded-full bg-ledger-primary"
                />
                <i :class="[link.icon, 'w-5 shrink-0 text-center']" />
                <span v-if="showExpandedContent">{{ link.label }}</span>
            </Link>
        </nav>

        <div class="border-t border-ledger-border pt-4">
            <div class="flex items-center gap-3 rounded-lg border border-ledger-border bg-ledger-bg p-2">
                <span class="grid size-10 shrink-0 place-items-center rounded-md bg-ledger-elevated text-xs font-black text-ledger-text">
                    {{ initials }}
                </span>
                <div v-if="showExpandedContent" class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-ledger-text">{{ user?.name || "User" }}</p>
                    <p class="truncate text-xs text-ledger-muted">{{ user?.email || "" }}</p>
                </div>
                <LogoutButton
                    v-if="showExpandedContent"
                    icon-only
                />
            </div>
        </div>
    </aside>
</template>
