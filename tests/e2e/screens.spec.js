import { test, expect } from '@playwright/test';
import { login, expectNoServerError } from './helpers.js';

test.describe('the four screens render for an admin', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    const screens = [
        ['languages',   '/translator/languages'],
        ['translators', '/translator/translators'],
        ['settings',    '/translator/settings'],
        ['manual',      '/translator/manual'],
    ];

    for (const [name, path] of screens) {
        test(`${name} renders without a server error`, async ({ page }) => {
            const response = await page.goto(path);

            expect(response.status()).toBe(200);
            await expectNoServerError(page);
        });
    }

    test('the manual documents the artisan commands', async ({ page }) => {
        await page.goto('/translator/manual');

        // The manual previously documented none of the console commands.
        await expect(page.locator('body')).toContainText('languages:import-translations');
    });

    test('settings exposes the multi-host toggle', async ({ page }) => {
        await page.goto('/translator/settings');

        await expect(page.locator('body')).toContainText(/multi.?host/i);
    });
});
