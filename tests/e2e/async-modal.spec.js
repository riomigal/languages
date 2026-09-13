import { test, expect } from '@playwright/test';
import { login, resetDatabase, openTranslations, settingControl } from './helpers.js';

async function openEditor(page) {
    await page.getByRole('row').filter({ has: page.getByRole('cell', { name: 'welcome', exact: true }) })
        .getByRole('button', { name: 'Translate', exact: true }).click();
    const modal = page.locator('#edit-translation-modal');
    await expect(modal.getByRole('textbox')).toHaveValue('Welcome home');
    return modal;
}

test.describe('translation suggestions', () => {
    test.beforeEach(async ({ page }) => {
        resetDatabase();
        await login(page);
        await page.getByRole('navigation').getByRole('link', { name: 'Settings', exact: true }).click();
        await Promise.all([
            page.waitForNavigation(),
            page.locator('[id="setting.enable_open_ai_translations"]').locator('label').click(),
        ]);
        await expect(settingControl(page, 'enable_open_ai_translations')).toBeChecked();
        await openTranslations(page);
    });

    test('a suggestion is a draft and an in-flight response cannot replace user edits', async ({ page }) => {
        let respond;
        await page.route('**/translations/*/*/suggest', route => new Promise(resolve => {
            respond = async value => {
                await route.fulfill({ json: { value } });
                resolve();
            };
        }));
        const modal = await openEditor(page);
        const suggest = modal.getByRole('button', { name: 'Translate with OPEN AI', exact: true });
        const draft = modal.getByRole('textbox');
        await suggest.click();
        await expect(suggest).toBeDisabled();
        await expect.poll(() => typeof respond).toBe('function');
        await respond('AI draft');
        await expect(draft).toHaveValue('AI draft');
        await expect(suggest).toBeEnabled();
        await modal.getByRole('button', { name: 'Close modal', exact: true }).click();
        await page.reload();
        await openEditor(page); // Nothing was saved by the suggestion.

        respond = undefined;
        await suggest.click();
        await expect.poll(() => typeof respond).toBe('function');
        await draft.fill('My unsaved draft');
        await respond('A later AI suggestion');
        await expect(draft).toHaveValue('My unsaved draft');
        await expect(modal.getByText('A later AI suggestion', { exact: true })).toBeVisible();
        await expect(suggest).toBeEnabled();
        await modal.getByRole('button', { name: 'Use suggestion', exact: true }).click();
        await expect(draft).toHaveValue('A later AI suggestion');
        await modal.getByRole('button', { name: 'Update Translation', exact: true }).click();
        await expect(modal).toBeHidden();
        await page.reload();
        await expect(page.getByRole('cell', { name: 'A later AI suggestion', exact: true })).toBeVisible();
    });

    test('a failed suggestion keeps the draft and re-enables controls for retry', async ({ page }) => {
        let attempts = 0;
        await page.route('**/translations/*/*/suggest', route => {
            attempts++;
            return route.fulfill(attempts === 1
                ? { status: 502, json: { message: 'Suggestion service unavailable.' } }
                : { json: { value: 'Recovered suggestion' } });
        });
        const modal = await openEditor(page);
        const suggest = modal.getByRole('button', { name: 'Translate with OPEN AI', exact: true });
        await suggest.click();
        await expect(modal.getByRole('alert')).toContainText('Suggestion service unavailable.');
        await expect(modal.getByRole('textbox')).toHaveValue('Welcome home');
        await expect(suggest).toBeEnabled();
        await expect(modal.getByRole('button', { name: 'Update Translation', exact: true })).toBeEnabled();
        await suggest.click();
        await expect(modal.getByRole('textbox')).toHaveValue('Recovered suggestion');
        await expect(suggest).toBeEnabled();
        await modal.getByRole('button', { name: 'Close modal', exact: true }).click();
        await page.reload();
        await openEditor(page);
    });
});
