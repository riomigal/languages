import { test, expect } from '@playwright/test';
import { ADMIN, TRANSLATOR, login, resetDatabase, formField, expectNoServerError } from './helpers.js';

const NEW_TRANSLATOR = {
    email: 'new-translator@example.test',
    password: 'new-translator-password',
    first_name: 'Jamie',
    last_name: 'Example',
    phone: '+41000000002',
};

function translatorRow(page, email = NEW_TRANSLATOR.email) {
    return page.getByRole('row').filter({
        has: page.getByRole('cell', { name: email, exact: true }),
    });
}

async function createTranslator(page) {
    await page.getByRole('button', { name: 'Create Translator', exact: true }).click();
    const form = page.locator('#createOrUpdateForm');
    await expect(form).toBeVisible();
    for (const [name, value] of Object.entries(NEW_TRANSLATOR)) {
        await formField(form, name).fill(value);
    }
    await formField(form, 'password_confirmation').fill(NEW_TRANSLATOR.password);
    await expect(form.locator('#admin').getByRole('checkbox')).not.toBeChecked();
    await form.getByRole('button', { name: 'Languages', exact: true }).click();
    await form.getByRole('checkbox', { name: 'English', exact: true }).check();
    await form.getByRole('button', { name: 'Languages', exact: true }).click();
    await form.getByRole('button', { name: 'Create', exact: true }).click();
    await expect(form).toHaveCount(0);
    await expect(translatorRow(page)).toBeVisible();
}

test.describe('translators', () => {
    test.beforeEach(async ({ page }) => {
        resetDatabase();
        await login(page);
        await page.getByRole('navigation').getByRole('link', { name: 'Translators', exact: true }).click();
        await expect(page.getByRole('heading', { name: 'Translators', exact: true })).toBeVisible();
    });

    // BUG-E2E-05: Clicking Create Translator does not open the form.
    test.fixme('admin creates a translator that remains in the list after reload', async ({ page }) => {
        await expect(translatorRow(page)).toHaveCount(0);
        await createTranslator(page);
        for (const value of [NEW_TRANSLATOR.first_name, NEW_TRANSLATOR.last_name, NEW_TRANSLATOR.phone, 'English']) {
            await expect(translatorRow(page).getByRole('cell', { name: value, exact: true })).toBeVisible();
        }

        await page.reload();
        await expectNoServerError(page);
        await expect(translatorRow(page)).toBeVisible();
        for (const value of [NEW_TRANSLATOR.first_name, NEW_TRANSLATOR.last_name, NEW_TRANSLATOR.phone, 'English']) {
            await expect(translatorRow(page).getByRole('cell', { name: value, exact: true })).toBeVisible();
        }
    });

    // BUG-E2E-05: Clicking Edit does not open the translator form.
    test.fixme('editing a translator persists the changed profile', async ({ page }) => {
        const original = {
            email: TRANSLATOR.email,
            first_name: 'Regular',
            last_name: 'Translator',
            phone: '+41000000001',
        };
        const row = translatorRow(page, original.email);
        await row.getByText('Edit', { exact: true }).click();
        await expect(page.locator('#createOrUpdateForm')).toBeVisible();
        const edited = {
            email: 'edited-translator@example.test',
            first_name: 'Taylor',
            last_name: 'Updated',
            phone: '+41000000003',
        };
        for (const [name, value] of Object.entries(edited)) {
            await expect(formField(page, name)).toHaveValue(original[name]);
            await formField(page, name).fill(value);
        }
        await page.getByRole('button', { name: 'Update', exact: true }).click();
        await expect(page.locator('#createOrUpdateForm')).toHaveCount(0);
        await expect(row).toHaveCount(0);
        await expect(translatorRow(page, edited.email)).toBeVisible();

        await page.reload();
        await expectNoServerError(page);
        await expect(row).toHaveCount(0);
        for (const value of Object.values(edited)) {
            await expect(translatorRow(page, edited.email).getByRole('cell', { name: value, exact: true })).toBeVisible();
        }
        await translatorRow(page, edited.email).getByText('Edit', { exact: true }).click();
        for (const [name, value] of Object.entries(edited)) {
            await expect(formField(page, name)).toHaveValue(value);
        }
    });

    // BUG-E2E-05: The broken Edit action blocks the language assignment form.
    test.fixme('assigning another language persists both selected languages', async ({ page }) => {
        const row = translatorRow(page, TRANSLATOR.email);
        await row.getByText('Edit', { exact: true }).click();
        const form = page.locator('#createOrUpdateForm');
        await expect(form).toBeVisible();
        await form.getByRole('button', { name: 'Languages', exact: true }).click();
        await expect(form.getByRole('checkbox', { name: 'English', exact: true })).toBeChecked();
        await expect(form.getByRole('checkbox', { name: 'German', exact: true })).not.toBeChecked();
        await form.getByRole('checkbox', { name: 'German', exact: true }).check();
        await form.getByRole('button', { name: 'Languages', exact: true }).click();
        await form.getByRole('button', { name: 'Update', exact: true }).click();
        await expect(form).toHaveCount(0);
        await expect(row.getByRole('cell', { name: 'English, German', exact: true })).toBeVisible();

        await page.reload();
        await expect(row.getByRole('cell', { name: 'English, German', exact: true })).toBeVisible();
        await row.getByText('Edit', { exact: true }).click();
        await form.getByRole('button', { name: 'Languages', exact: true }).click();
        await expect(form.getByRole('checkbox', { name: 'English', exact: true })).toBeChecked();
        await expect(form.getByRole('checkbox', { name: 'German', exact: true })).toBeChecked();
    });

    test('deleting a translator removes it from the list after reload', async ({ page }) => {
        const row = translatorRow(page, TRANSLATOR.email);
        await expect(row).toBeVisible();
        await row.getByText('Delete', { exact: true }).click();
        await expect(row).toHaveCount(0);
        await expect(translatorRow(page, ADMIN.email)).toBeVisible();

        await page.reload();
        await expectNoServerError(page);
        await expect(row).toHaveCount(0);
        await expect(translatorRow(page, ADMIN.email)).toBeVisible();
        await expect(page.getByRole('row').filter({ has: page.getByRole('cell') })).toHaveCount(1);
    });
});
