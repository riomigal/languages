import { test, expect } from '@playwright/test';
import { TRANSLATOR, login, resetDatabase, openTranslations, expectNoServerError } from './helpers.js';

test.describe('non-admin permissions', () => {
    test.beforeEach(async ({ page }) => {
        resetDatabase();
        // This real login also verifies seed.sh created usable credentials.
        await login(page, TRANSLATOR);
        await expect(page.getByRole('heading', { name: 'Languages', exact: true })).toBeVisible();
        await expectNoServerError(page);
    });

    test('a translator sees assigned languages without admin navigation or language actions', async ({ page }) => {
        const assertPermissions = async () => {
            const navigation = page.getByRole('navigation');
            await expect(navigation.getByRole('link', { name: 'Languages', exact: true })).toBeVisible();
            await expect(navigation.getByRole('link', { name: 'Manual', exact: true })).toBeVisible();
            await expect(navigation.getByRole('button', { name: 'Logout', exact: true })).toBeVisible();
            await expect(navigation.getByRole('link', { name: 'Settings', exact: true })).toHaveCount(0);
            await expect(navigation.getByRole('link', { name: 'Translators', exact: true })).toHaveCount(0);

            const rows = page.getByRole('row').filter({ has: page.getByRole('cell') });
            await expect(rows).toHaveCount(1);
            await expect(rows).toContainText('English');
            await expect(rows.getByRole('link', { name: 'View', exact: true })).toBeVisible();
            await expect(page.getByRole('cell', { name: 'German', exact: true })).toHaveCount(0);
            for (const name of ['Add Language', 'Import Languages', 'Import Translations', 'Find Missing Translations', 'Approve (All Languages) Translations', 'Export All Languages', 'Export All Translated Models', 'Delete running Batch (Jobs)']) {
                await expect(page.getByRole('button', { name, exact: true })).toHaveCount(0);
            }
            for (const name of ['Delete', 'Edit']) {
                await expect(rows.getByText(name, { exact: true })).toHaveCount(0);
            }
        };
        await assertPermissions();
        await page.reload();
        await assertPermissions();
    });

    test('a translator can see translations without approval, export or request actions', async ({ page }) => {
        await openTranslations(page);
        const assertPermissions = async () => {
            const rows = page.getByRole('row').filter({ has: page.getByRole('cell') });
            await expect(rows).toHaveCount(6);
            await expect(rows.getByText('Translate', { exact: true })).toHaveCount(6);
            for (const name of ['Approve', 'Approve (en) Translations', 'Export Language', 'Export Translated Models']) {
                await expect(page.getByRole('button', { name, exact: true })).toHaveCount(0);
            }
            for (const name of ['Request translation', 'Remove translation request', 'Restore', 'Delete', 'Edit']) {
                await expect(rows.getByText(name, { exact: true })).toHaveCount(0);
            }
        };
        await assertPermissions();
        await page.reload();
        await assertPermissions();
    });

    for (const screen of ['settings', 'translators']) {
        test(`a translator cannot open the ${screen} screen directly`, async ({ page }) => {
            const response = await page.goto(`/translator/${screen}`);
            expect(response.status()).toBe(403);
            await expect(page.getByText('Forbidden', { exact: true })).toBeVisible();
            await expectNoServerError(page);
            await expect(page.getByRole('heading', { name: new RegExp(`^${screen}$`, 'i') })).toHaveCount(0);
            await expect(page.getByRole('button', { name: 'Create Translator', exact: true })).toHaveCount(0);
            await expect(page.getByText('Import Settings', { exact: true })).toHaveCount(0);

            const reloaded = await page.reload();
            expect(reloaded.status()).toBe(403);
            await expect(page.getByText('Forbidden', { exact: true })).toBeVisible();
            // A forbidden screen must not log the translator out.
            await page.goto('/translator/languages');
            await expect(page.getByRole('heading', { name: 'Languages', exact: true })).toBeVisible();
        });
    }
});
