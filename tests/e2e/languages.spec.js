import { test, expect } from '@playwright/test';
import { login, resetDatabase, expectNoServerError } from './helpers.js';

test.describe('languages', () => {
    test.beforeEach(async ({ page }) => {
        resetDatabase();
        await login(page);
    });

    // BUG-E2E-01: Clicking Add Language does not open the form.
    test.fixme('admin adds a language that remains in the table after reload', async ({ page }) => {
        const french = page.getByRole('row').filter({
            has: page.getByRole('cell', { name: 'French', exact: true }),
        });
        await expect(french).toHaveCount(0);
        await page.getByRole('button', { name: 'Add Language', exact: true }).click();
        await expect(page.getByRole('combobox')).toBeVisible();
        await page.getByRole('combobox').selectOption({ label: 'French' });
        await page.getByRole('button', { name: 'Add', exact: true }).click();

        await expect(french).toBeVisible();
        await expect(french.getByRole('cell', { name: 'fr', exact: true })).toBeVisible();
        await expect(french.getByRole('cell', { name: 'français', exact: true })).toBeVisible();
        await page.reload();
        await expectNoServerError(page);
        await expect(french).toBeVisible();
        await expect(french.getByRole('link', { name: 'View', exact: true })).toBeVisible();
    });

    // BUG-E2E-02: Typing in search leaves every language row visible.
    test.fixme('search filters languages and clearing it restores the list', async ({ page }) => {
        const rows = page.getByRole('row').filter({ has: page.getByRole('cell') });
        await expect(rows).toHaveCount(2);
        await page.getByRole('searchbox').fill('German');
        await expect(rows).toHaveCount(1);
        await expect(rows.getByRole('cell', { name: 'German', exact: true })).toBeVisible();

        await page.getByRole('searchbox').fill('no-such-language');
        await expect(rows).toHaveCount(0);
        await page.getByRole('searchbox').clear();
        await expect(rows).toHaveCount(2);
        await expect(rows.filter({ has: page.getByRole('cell', { name: 'English', exact: true }) })).toBeVisible();
        await expect(rows.getByRole('cell', { name: 'German', exact: true })).toBeVisible();
    });

    // BUG-E2E-01: Clicking Add Language does not open the form.
    test.fixme('the add-language form opens and closes without creating a language', async ({ page }) => {
        const select = page.getByRole('combobox');
        await expect(select).toHaveCount(0);
        await page.getByRole('button', { name: 'Add Language', exact: true }).click();
        await expect(select).toBeVisible();
        await select.selectOption({ label: 'French' });
        await page.getByRole('button', { name: 'Close', exact: true }).click();
        await expect(select).toHaveCount(0);
        await expect(page.getByRole('cell', { name: 'French', exact: true })).toHaveCount(0);

        await page.getByRole('button', { name: 'Add Language', exact: true }).click();
        await expect(select).toBeVisible();
        await page.getByRole('button', { name: 'Close', exact: true }).click();
        await page.reload();
        await expect(select).toHaveCount(0);
        await expect(page.getByRole('cell', { name: 'French', exact: true })).toHaveCount(0);
    });
});
