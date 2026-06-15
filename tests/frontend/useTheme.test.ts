import { beforeEach, describe, expect, it, vi } from "vitest";

describe("useTheme", () => {
    beforeEach(() => {
        localStorage.clear();
        document.documentElement.className = "";
        vi.resetModules();
    });

    it("defaults to dark and persists a light theme toggle", async () => {
        const { useTheme } = await import("@/composables/useTheme");
        const { isDark, toggleTheme } = useTheme();

        expect(isDark.value).toBe(true);

        toggleTheme();

        expect(isDark.value).toBe(false);
        expect(localStorage.getItem("bookflow-theme")).toBe("light");
        expect(document.documentElement.classList.contains("app-dark")).toBe(false);
    });

    it("applies dark mode to the document", async () => {
        localStorage.setItem("bookflow-theme", "light");
        const { useTheme } = await import("@/composables/useTheme");
        const { setTheme } = useTheme();

        setTheme("dark");

        expect(localStorage.getItem("bookflow-theme")).toBe("dark");
        expect(document.documentElement.classList.contains("app-dark")).toBe(true);
    });
});
