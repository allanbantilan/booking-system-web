import { computed, ref } from "vue";

export type AppTheme = "dark" | "light";

const theme = ref<AppTheme>(
    typeof localStorage !== "undefined" && localStorage.getItem("bookflow-theme") === "light"
        ? "light"
        : "dark",
);

const applyTheme = (value: AppTheme) => {
    theme.value = value;
    const root = document.documentElement;
    // Arm the transition BEFORE flipping colors, else the change jumps instantly.
    root.classList.add("theme-changing");
    window.requestAnimationFrame(() => {
        root.classList.toggle("app-dark", value === "dark");
        window.setTimeout(() => root.classList.remove("theme-changing"), 420);
    });
    localStorage.setItem("bookflow-theme", value);
};

export const useTheme = () => ({
    theme: computed(() => theme.value),
    isDark: computed(() => theme.value === "dark"),
    setTheme: applyTheme,
    toggleTheme: () => applyTheme(theme.value === "dark" ? "light" : "dark"),
});
