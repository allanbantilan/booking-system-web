import { test, expect, request } from "@playwright/test";

const MAILPIT = "http://localhost:8025";

test("forgot password sends a working reset link", async ({ page }) => {
    // Start from an empty inbox so we grab the right message.
    const api = await request.newContext();
    await api.delete(`${MAILPIT}/api/v1/messages`);

    // Trigger the reset email.
    // ForgotPassword.vue: InputLabel renders a <label> without a `for` attr,
    // so fall back to the input's id attribute.
    await page.goto("/forgot-password");
    await page.locator("#email").fill("e2e@example.com");
    // Button text: "Email Password Reset Link"
    await page.getByRole("button", { name: "Email Password Reset Link" }).click();

    // Wait for the success status message that Fortify shows after sending.
    await expect(page.locator("body")).toContainText("We have emailed", { timeout: 10_000 });

    // Read the latest message from Mailpit and extract the reset URL.
    const list = await api.get(`${MAILPIT}/api/v1/messages`);
    const messages = (await list.json()).messages;
    expect(messages.length).toBeGreaterThan(0);

    const detail = await api.get(`${MAILPIT}/api/v1/message/${messages[0].ID}`);
    const detailJson = await detail.json();
    const body = detailJson.HTML || detailJson.Text;
    const match = body.match(/https?:\/\/[^\s"'<>]*reset-password[^\s"'<>]*/);
    expect(match).not.toBeNull();
    const resetUrl = match![0].replace(/&amp;/g, "&");

    // Follow the link and set a new password.
    // ResetPassword.vue: InputLabel also renders without `for`, use id selectors.
    await page.goto(resetUrl);
    await page.locator("#password").fill("new-password-123");
    await page.locator("#password_confirmation").fill("new-password-123");
    // Button text: "Reset Password"
    await page.getByRole("button", { name: "Reset Password" }).click();

    // After reset, Fortify redirects to /login.
    await expect(page).toHaveURL(/login/, { timeout: 10_000 });

    // Sign in with the new password.
    // Login.vue: use id selectors to avoid strict-mode clash with the
    // "Show password" toggle button that also matches getByLabel("Password").
    // Submit button text: "Sign in"
    await page.locator("#email").fill("e2e@example.com");
    await page.locator("#password").fill("new-password-123");
    await page.getByRole("button", { name: "Sign in" }).click();

    await expect(page).toHaveURL(/dashboard/, { timeout: 10_000 });
});
