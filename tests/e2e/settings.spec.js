import { test, expect } from '@playwright/test';
import { login, resetDatabase, formField, settingControl, expectNoServerError } from './helpers.js';

async function toggleSetting(page, name) {
    // The visible switch is an unlabeled native label around a checkbox.
    await page.locator(`[id="setting.${name}"]`).locator('label').click();
}

test.describe('settings', () => {
    test.beforeEach(async ({ page }) => {
        resetDatabase();
        await login(page);
        await page.getByRole('navigation').getByRole('link', { name: 'Settings', exact: true }).click();
        await expect(page.getByRole('heading', { name: 'Settings', exact: true })).toBeVisible();
    });

    // BUG-E2E-04: Allow deleting languages changes visually but is not saved.
    test.fixme('toggling Allow deleting languages persists across reloads', async ({ page, context }) => {
        const toggle = settingControl(page, 'allow_deleting_languages');
        await expect(page.getByText('Allow deleting languages.', { exact: true })).toBeVisible();
        await expect(toggle).toBeChecked();
        await toggleSetting(page, 'allow_deleting_languages');
        await expect(toggle).not.toBeChecked();

        // Read through a second tab while autosave may still be in flight. This
        // avoids aborting a pending save by immediately reloading the editor.
        const saved = await context.newPage();
        try {
            await expect(async () => {
                await saved.goto('/translator/settings');
                await expect(settingControl(saved, 'allow_deleting_languages')).not.toBeChecked({ timeout: 500 });
            }).toPass({ timeout: 8_000 });
            await page.reload();
            await expectNoServerError(page);
            await expect(toggle).not.toBeChecked();

            await toggleSetting(page, 'allow_deleting_languages');
            await expect(toggle).toBeChecked();
            await expect(async () => {
                await saved.reload();
                await expect(settingControl(saved, 'allow_deleting_languages')).toBeChecked({ timeout: 500 });
            }).toPass({ timeout: 8_000 });
            await page.reload();
            await expect(toggle).toBeChecked();
        } finally {
            await saved.close();
        }
    });

    test('multi-host coordination makes domains required only while enabled', async ({ page, context }) => {
        const toggle = settingControl(page, 'enable_multi_host');
        const domains = formField(page, 'setting.domains');
        await expect(toggle).not.toBeChecked();
        await expect(domains).toHaveValue('');
        await expect(domains).not.toHaveAttribute('required');

        await toggleSetting(page, 'enable_multi_host');
        await expect(toggle).toBeChecked();
        await expect(domains).toHaveAttribute('required', '');

        // Enabling with blank domains must not persist the invalid setting.
        await page.reload();
        await expect(toggle).not.toBeChecked();
        await expect(domains).not.toHaveAttribute('required');

        const saved = await context.newPage();
        try {
            await domains.fill('https://translations.example.test');
            await domains.press('Tab');
            await expect(async () => {
                await saved.goto('/translator/settings');
                await expect(formField(saved, 'setting.domains')).toHaveValue('https://translations.example.test', { timeout: 500 });
            }).toPass({ timeout: 8_000 });
            await toggleSetting(page, 'enable_multi_host');
            await expect(domains).toHaveAttribute('required', '');
            await expect(async () => {
                await saved.reload();
                await expect(settingControl(saved, 'enable_multi_host')).toBeChecked({ timeout: 500 });
            }).toPass({ timeout: 8_000 });
            await page.reload();
            await expect(toggle).toBeChecked();
            await expect(domains).toHaveAttribute('required', '');

            await toggleSetting(page, 'enable_multi_host');
            await expect(domains).not.toHaveAttribute('required');
            await domains.clear();
            await domains.press('Tab');
            await expect(async () => {
                await saved.reload();
                await expect(settingControl(saved, 'enable_multi_host')).not.toBeChecked({ timeout: 500 });
                await expect(formField(saved, 'setting.domains')).toHaveValue('', { timeout: 500 });
            }).toPass({ timeout: 8_000 });
            await page.reload();
            await expect(toggle).not.toBeChecked();
            await expect(domains).toHaveValue('');
            await expect(domains).not.toHaveAttribute('required');
            await expectNoServerError(page);
        } finally {
            await saved.close();
        }
    });
});
