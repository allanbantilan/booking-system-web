<script setup lang="ts">
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import Button from "primevue/button";

defineProps<{
    collapsed?: boolean;
    mobile?: boolean;
}>();

const emit = defineEmits<{
    navigate: [];
    toggle: [];
}>();

const page = usePage();
const appName = computed(() => String(page.props.appName || "BookFlow"));
const user = computed(() => (page.props as any).auth?.user as { name?: string; email?: string } | undefined);
const routeUrl = (name: string) => route(name);

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

const logout = () => router.post(route("logout"));
</script>

<template>
    <aside
        class="flex h-full flex-col border-r border-ledger-border bg-ledger-surface/95 px-3 py-4 backdrop-blur-xl transition-[width] duration-200"
        :class="collapsed && !mobile ? 'w-20' : 'w-72'"
    >
        <div class="flex items-center gap-3 px-2">
            <Link
                :href="routeUrl('dashboard')"
                class="flex min-w-0 flex-1 items-center gap-3"
                @click="emit('navigate')"
            >
                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-cyan-500 text-slate-950 shadow-lg shadow-cyan-500/20">
                    <i class="pi pi-bolt text-lg" />
                </span>
                <span v-if="!collapsed || mobile" class="min-w-0">
                    <strong class="block truncate font-display text-lg text-ledger-text">{{ appName }}</strong>
                    <span class="block text-[10px] font-bold uppercase tracking-[0.22em] text-ledger-muted">Booking ledger</span>
                </span>
            </Link>

            <Button
                v-if="!mobile"
                text
                rounded
                severity="secondary"
                :icon="collapsed ? 'pi pi-angle-right' : 'pi pi-angle-left'"
                aria-label="Toggle sidebar"
                @click="emit('toggle')"
            />
        </div>

        <div v-if="!collapsed || mobile" class="mx-2 mt-7 border-t border-ledger-border pt-4">
            <p class="px-3 text-[10px] font-bold uppercase tracking-[0.22em] text-ledger-muted">Workspace</p>
        </div>

        <nav class="mt-4 flex-1 space-y-1.5" aria-label="Customer workspace">
            <Link
                v-for="link in links"
                :key="link.route"
                :href="routeUrl(link.route)"
                :title="collapsed && !mobile ? link.label : undefined"
                class="group relative flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-semibold transition"
                :class="isActive(link.matches)
                    ? 'bg-cyan-500/12 text-ledger-primary'
                    : 'text-ledger-muted hover:bg-ledger-elevated hover:text-ledger-text'"
                @click="emit('navigate')"
            >
                <span
                    v-if="isActive(link.matches)"
                    class="absolute inset-y-2 left-0 w-0.5 rounded-full bg-cyan-400 shadow-[0_0_12px_rgba(34,211,238,0.8)]"
                />
                <i :class="[link.icon, 'w-5 shrink-0 text-center']" />
                <span v-if="!collapsed || mobile">{{ link.label }}</span>
            </Link>
        </nav>

        <div class="border-t border-ledger-border pt-4">
            <div class="flex items-center gap-3 rounded-xl bg-ledger-elevated p-2">
                <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-orange-500/15 text-xs font-black text-orange-400">
                    {{ initials }}
                </span>
                <div v-if="!collapsed || mobile" class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-ledger-text">{{ user?.name || "User" }}</p>
                    <p class="truncate text-xs text-ledger-muted">{{ user?.email || "" }}</p>
                </div>
                <Button
                    v-if="!collapsed || mobile"
                    text
                    rounded
                    severity="danger"
                    icon="pi pi-sign-out"
                    aria-label="Log out"
                    @click="logout"
                />
            </div>
        </div>
    </aside>
</template>
