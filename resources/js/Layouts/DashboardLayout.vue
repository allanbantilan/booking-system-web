<script setup lang="ts">
import { Head, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import Button from "primevue/button";
import Drawer from "primevue/drawer";
import Toast from "primevue/toast";
import Sidebar from "@/Components/Dashboard/Sidebar.vue";
import { useTheme } from "@/composables/useTheme";

const props = withDefaults(defineProps<{ title?: string }>(), { title: "" });
const page = usePage();
const appName = computed(() => String(page.props.appName || "BookBound"));
const pageTitle = computed(() => props.title ? `${props.title} | ${appName.value}` : appName.value);
const mobileOpen = ref(false);
const collapsed = ref(localStorage.getItem("bookflow-sidebar-collapsed") === "true");
const { isDark, toggleTheme } = useTheme();

const toggleSidebar = () => {
    collapsed.value = !collapsed.value;
    localStorage.setItem("bookflow-sidebar-collapsed", String(collapsed.value));
};
</script>

<template>
    <Head :title="pageTitle" />
    <Toast />

    <div class="min-h-screen bg-ledger-bg text-ledger-text">
        <div class="relative flex min-h-screen">
            <div class="fixed inset-y-0 left-0 z-30 hidden lg:block">
                <Sidebar :collapsed="collapsed" @toggle="toggleSidebar" />
            </div>

            <Drawer
                v-model:visible="mobileOpen"
                position="left"
                :show-close-icon="false"
                :pt="{ content: { class: '!p-0' }, root: { class: '!w-72' } }"
            >
                <Sidebar mobile @navigate="mobileOpen = false" />
            </Drawer>

            <div
                class="flex min-w-0 flex-1 flex-col transition-[margin] duration-200"
                :class="collapsed ? 'lg:ml-20' : 'lg:ml-72'"
            >
                <header class="sticky top-0 z-20 border-b border-ledger-border bg-ledger-bg/95">
                    <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                        <Button
                            class="lg:!hidden"
                            text
                            rounded
                            severity="secondary"
                            icon="pi pi-bars"
                            aria-label="Open navigation"
                            @click="mobileOpen = true"
                        />
                        <div class="min-w-0 flex-1">
                            <h1 class="truncate font-display text-lg font-bold text-ledger-text">{{ title }}</h1>
                        </div>
                        <Button
                            text
                            rounded
                            severity="secondary"
                            :icon="isDark ? 'pi pi-sun' : 'pi pi-moon'"
                            :aria-label="isDark ? 'Use light theme' : 'Use dark theme'"
                            @click="toggleTheme"
                        />
                    </div>
                </header>

                <main class="relative flex-1 px-4 py-5 sm:px-6 lg:px-8 lg:py-6">
                    <div :key="page.url" class="bf-page-in mx-auto max-w-[88rem]">
                        <slot />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
