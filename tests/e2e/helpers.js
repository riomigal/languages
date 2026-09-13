import { expect } from '@playwright/test';

// Created by the package's own create_admin_translator migration, so the e2e
// database needs no seeding beyond migrate:fresh.
export const ADMIN = {
    email: 'admin@admin.com',
    password: 'aaaaaaaa',
};

/**
 * Log in through the real form rather than by seeding a session, so the test
 * exercises the actual submit path. A control that renders but is not wired up
 * is precisely the failure these tests exist to catch.
 */
export async function login(page, credentials = ADMIN) {
    await page.goto('/translator/login');
    await page.fill('input#email', credentials.email);
    await page.fill('input#password', credentials.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/translator\/(languages|translators)/, { timeout: 15_000 });
}

/** Assert the page is not an error page. */
export async function expectNoServerError(page) {
    await expect(page.locator('body')).not.toContainText('Internal Server Error');
    await expect(page.locator('body')).not.toContainText('SQLSTATE');
}
