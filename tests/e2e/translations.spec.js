import { test, expect } from '@playwright/test';
import { login, resetDatabase, openTranslations, expectNoServerError } from './helpers.js';

// Visible keys and values from seed.sh. Each state matches a different subset.
const VALUES = {
    welcome: 'Welcome home',
    checkout: 'Finish your order',
    profile: 'Your profile',
    vendor_notice: 'Vendor notice',
    vendor_pending: 'Vendor draft',
    vendor_ready: 'Vendor ready',
};
const KEYS = Object.keys(VALUES);
const FILTERS = [
    { label: 'Needs Translation', query: 'needs_translation', matches: ['checkout', 'vendor_pending'] },
    { label: 'Approved', query: 'approved', matches: ['welcome', 'vendor_notice', 'vendor_ready'] },
    { label: 'Updated', query: 'updated_translation', matches: ['profile', 'vendor_pending'] },
    { label: 'Is Vendor', query: 'is_vendor', matches: ['vendor_notice', 'vendor_pending', 'vendor_ready'] },
    { label: 'Exported', query: 'exported', matches: ['welcome', 'vendor_ready'] },
];

function translationRow(page, key) {
    return page.getByRole('row').filter({
        has: page.getByRole('cell', { name: key, exact: true }),
    });
}

async function expectKeys(page, keys) {
    await expect(page.getByRole('row').filter({ has: page.getByRole('cell') })).toHaveCount(keys.length);
    for (const key of KEYS) {
        if (keys.includes(key)) {
            await expect(translationRow(page, key)).toBeVisible();
        } else {
            await expect(translationRow(page, key)).toHaveCount(0);
        }
    }
}

test.describe('translations', () => {
    test.beforeEach(async ({ page }) => {
        resetDatabase();
        await login(page);
        await openTranslations(page);
    });

    test('the language screen lists translation keys and current values', async ({ page }) => {
        for (const heading of ['Key', 'Content', 'Old Content', 'Approved', 'Needs Translation']) {
            await expect(page.getByRole('columnheader', { name: heading, exact: true })).toBeVisible();
        }
        await expectKeys(page, KEYS);
        for (const [key, value] of Object.entries(VALUES)) {
            await expect(translationRow(page, key).getByRole('cell', { name: value, exact: true })).toBeVisible();
            await expect(translationRow(page, key).getByText('Translate', { exact: true })).toBeVisible();
        }
        await page.reload();
        await expectNoServerError(page);
        await expectKeys(page, KEYS);
    });

    test('search filters by key and content and clearing it restores all rows', async ({ page }) => {
        await expectKeys(page, KEYS);
        await page.getByRole('searchbox').fill('checkout');
        await expectKeys(page, ['checkout']);
        await page.getByRole('searchbox').fill('Welcome home');
        await expectKeys(page, ['welcome']);
        await page.getByRole('searchbox').fill('no-such-translation');
        await expectKeys(page, []);
        await page.getByRole('searchbox').clear();
        await expectKeys(page, KEYS);
    });

    for (const { label, query, matches } of FILTERS) {
        const nonMatches = KEYS.filter(key => !matches.includes(key));

        test(`${label} opens a bookmarked filter and keeps its rows after reload`, async ({ page }) => {
            // Bookmarks and reloads must reconstruct the same filter state.
            const url = new URL(page.url());
            for (const [value, keys] of [['true', matches], ['false', nonMatches], [null, KEYS]]) {
                if (value === null) {
                    url.searchParams.delete(query);
                } else {
                    url.searchParams.set(query, value);
                }
                await page.goto(url.href);
                await expectKeys(page, keys);
                await page.reload();
                await expectNoServerError(page);
                await expectKeys(page, keys);
            }
        });

        test(`${label} cycles from all to matching to non-matching and back to all`, async ({ page }) => {
            await expectKeys(page, KEYS);
            await page.getByRole('button', { name: 'State Filters', exact: true }).click();
            const filter = page.getByRole('button', { name: label, exact: true });
            await expect(filter).toBeVisible();
            await filter.click();
            await expectKeys(page, matches);
            await filter.click();
            await expectKeys(page, nonMatches);
            await filter.click();
            await expectKeys(page, KEYS);
        });

        test(`${label} preserves each filter state in the URL across reloads`, async ({ page }) => {
            await page.getByRole('button', { name: 'State Filters', exact: true }).click();
            const filter = page.getByRole('button', { name: label, exact: true });
            await expect(filter).toBeVisible();
            for (const [value, keys] of [['true', matches], ['false', nonMatches], [null, KEYS]]) {
                await filter.click();
                await expectKeys(page, keys);
                await expect(page).toHaveURL(url => url.searchParams.get(query) === value);
                await page.reload();
                await expectNoServerError(page);
                await expectKeys(page, keys);
                await expect(page).toHaveURL(url => url.searchParams.get(query) === value);
                await page.getByRole('button', { name: 'State Filters', exact: true }).click();
            }
        });
    }

    test('the translate modal shows the current value and saves an edit across reloads', async ({ page }) => {
        const row = translationRow(page, 'welcome');
        await row.getByText('Translate', { exact: true }).click();
        const modal = page.locator('#edit-translation-modal');
        await expect(modal).toBeVisible();
        await expect(modal.getByText('e2e.welcome', { exact: true })).toBeVisible();
        await expect(modal.getByRole('textbox')).toHaveValue(VALUES.welcome);
        await modal.getByRole('textbox').fill('Welcome back, traveller');
        await modal.getByRole('button', { name: 'Update Translation', exact: true }).click();
        await expect(modal).toBeHidden();
        await expect(row.getByRole('cell', { name: 'Welcome back, traveller', exact: true })).toBeVisible();
        await expect(row.getByRole('cell', { name: VALUES.welcome, exact: true })).toBeVisible();

        await page.reload();
        await expectNoServerError(page);
        await expect(row.getByRole('cell', { name: 'Welcome back, traveller', exact: true })).toBeVisible();
        await row.getByText('Translate', { exact: true }).click();
        await expect(modal).toBeVisible();
        await expect(modal.getByRole('textbox')).toHaveValue('Welcome back, traveller');
        await modal.getByRole('button', { name: 'Close modal', exact: true }).click();
        await expect(modal).toBeHidden();
    });
});
