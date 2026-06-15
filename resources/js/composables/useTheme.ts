import { computed, ref } from "vue";

export type AppTheme = "dark" | "light";

const theme = ref<AppTheme>(
    typeof localStorage !== "undefined" && localStorage.getItem("bookflow-theme") === "light"
        ? "light"
        : "dark",
);

const applyTheme = (value: AppTheme) => {
    theme.value = value;
    document.documentElement.classList.toggle("app-dark", value === "dark");
    localStorage.setItem("bookflow-theme", value);
};

export const useTheme = () => ({
    theme: computed(() => theme.value),
    isDark: computed(() => theme.value === "dark"),
    setTheme: applyTheme,
    toggleTheme: () => applyTheme(theme.value === "dark" ? "light" : "dark"),
});
