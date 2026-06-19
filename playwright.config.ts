import { defineConfig } from "@playwright/test";

export default defineConfig({
    testDir: "./tests/e2e",
    timeout: 30_000,
    fullyParallel: false, // reset spec mutates the e2e user; keep specs serial
    workers: 1,
    use: {
        baseURL: "http://localhost",
        headless: true,
    },
});
