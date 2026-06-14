import { defineConfig, devices } from '@playwright/test';

/**
 * E2E config. Drives the real app via `artisan serve` on localhost:8000.
 * Club subdomains (e.g. smashclub.localhost) resolve to 127.0.0.1 in Chromium,
 * and SESSION_DOMAIN=localhost shares the session across them.
 */
export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    reporter: process.env.CI ? 'list' : [['list']],
    use: {
        // lvh.me resolves to 127.0.0.1; *.lvh.me too, so club subdomains + shared
        // session cookies work the same locally as in production.
        baseURL: process.env.E2E_BASE_URL ?? 'http://lvh.me:8000',
        trace: 'on-first-retry',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
    webServer: process.env.E2E_BASE_URL
        ? undefined
        : {
              command: 'php artisan serve --host=127.0.0.1 --port=8000',
              url: 'http://127.0.0.1:8000/up',
              reuseExistingServer: !process.env.CI,
              timeout: 120_000,
          },
});
