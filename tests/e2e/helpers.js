import { expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { join } from 'node:path';

// Created by the package's own create_admin_translator migration, so the e2e
// database needs no seeding beyond migrate:fresh.
export const ADMIN = {
    email: 'admin@admin.com',
    password: 'aaaaaaaa',
};

export const TRANSLATOR = {
    email: 'translator@example.test',
    password: 'translator-password',
};

// The file database is shared with the server, including a reused server.
// Keep workers: 1. Reset before login so no test depends on another test's edits.
export function resetDatabase() {
    const output = execFileSync(join(__dirname, 'seed.sh'), {
        encoding: 'utf8',
        stdio: 'pipe',
        timeout: 30_000,
    });
    // Tinker can print a PHP exception without returning a failing exit code.
    if (!output.includes('seeded: 2 languages, admin + non-admin, 6 translations')) {
        throw new Error(`E2E fixture setup did not finish:\n${output}`);
    }
}

// These controls currently put their id on the surrounding field, and their
// visual labels are not associated with the input. Scope by that stable id,
// then use the native role/type; do not depend on framework bindings or classes.
export function formField(page, id) {
    return page.locator(`[id="${id}"]`).locator('input');
}

export function settingControl(page, name) {
    return page.locator(`[id="setting.${name}"]`).getByRole('checkbox');
}

export async function openTranslations(page, language = 'English') {
    await page.getByRole('navigation').getByRole('link', { name: 'Languages', exact: true }).click();
    const row = page.getByRole('row').filter({
        has: page.getByRole('cell', { name: language, exact: true }),
    });
    await row.getByRole('link', { name: 'View', exact: true }).click();
    await expect(page.getByRole('heading', { name: `Translations ${language}`, exact: false })).toBeVisible();
    await expectNoServerError(page);
}

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
