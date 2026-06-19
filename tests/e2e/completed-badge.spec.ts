import { test, expect } from "@playwright/test";

test("past-checkout booking shows a Completed badge", async ({ page }) => {
    // Login.vue uses raw <label for="email"> and <label for="password">,
    // so getByLabel works. Submit button text is "Sign in".
    await page.goto("/login");
    await page.locator("#email").fill("badge@example.com");
    await page.locator("#password").fill("password");
    await page.getByRole("button", { name: "Sign in" }).click();

    await expect(page).toHaveURL(/dashboard/, { timeout: 10_000 });

    await page.goto("/bookings/history");

    // BookingHistory.vue renders a <span> with the text returned by
    // getReservationStatusLabel(), which returns "Completed" when is_completed is true.
    await expect(page.getByText("Completed", { exact: true }).first()).toBeVisible();
});
