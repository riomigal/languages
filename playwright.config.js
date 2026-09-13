import { defineConfig, devices } from '@playwright/test';

const PORT = process.env.E2E_PORT ?? '8099';

/**
 * End-to-end tests run against the package served standalone by Testbench, so
 * they need neither the sibling host application nor a running MySQL instance.
 *
 * These cover what PHPUnit structurally cannot: that the rendered controls are
 * actually wired up. A JavaScript-driven control can render perfectly and
 * still submit nothing, and a server-side test asserting on HTML will not notice.
 */
export default defineConfig({
    testDir: './tests/e2e',
    testMatch: '**/*.spec.js',
    fullyParallel: false,
    workers: 1,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? 'github' : 'list',

    use: {
        baseURL: `http://127.0.0.1:${PORT}`,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },

    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    ],

    webServer: {
        command: './tests/e2e/serve.sh',
        url: `http://127.0.0.1:${PORT}/api/version`,
        reuseExistingServer: !process.env.CI,
        timeout: 60_000,
        env: { PORT },
    },
});
