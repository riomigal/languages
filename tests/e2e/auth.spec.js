import { test, expect } from '@playwright/test';
import { ADMIN, login } from './helpers.js';

test.describe('authentication', () => {
    test('the login page renders for an anonymous visitor', async ({ page }) => {
        /**
         * Regression: the layout rendered <livewire:batch-execution/>
         * unconditionally while that component's template was wrapped entirely in
         * an is-admin @if. An anonymous visitor therefore got an empty render and
         * Livewire raised "missing root tag", making the login page a 500. No
         * server-side test caught it because none rendered the full layout while
         * logged out.
         */
        const response = await page.goto('/translator/login');

        expect(response.status()).toBe(200);
        await expect(page.locator('input#email')).toBeVisible();
        await expect(page.locator('input#password')).toBeVisible();
    });

    test('a valid admin can log in and reaches the languages screen', async ({ page }) => {
        await login(page);

        await expect(page).toHaveURL(/\/translator\/languages/);
    });

    test('invalid credentials are rejected and stay on the login page', async ({ page }) => {
        await page.goto('/translator/login');
        await page.fill('input#email', ADMIN.email);
        await page.fill('input#password', 'definitely-not-the-password');
        await page.click('button[type="submit"]');

        await expect(page.locator('body')).toContainText(/invalid/i);
        await expect(page).toHaveURL(/\/translator\/login/);
    });

    test('an anonymous visitor cannot reach an authenticated screen', async ({ page }) => {
        const response = await page.goto('/translator/languages');

        // Either redirected to login, or refused outright - never rendered.
        expect(response.status() === 403 || page.url().includes('login')).toBeTruthy();
    });
});
