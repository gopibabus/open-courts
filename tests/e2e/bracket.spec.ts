import { expect, test } from '@playwright/test';

// A 1×1 transparent PNG, used to exercise the match image upload.
const PNG = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==', 'base64');

/**
 * End-to-end on the seeded demo: open the 8-player Open Singles bracket, attach an image to a
 * match, record its result, and confirm the winner advances to the next round. Port-agnostic
 * via baseURL; logs in centrally and relies on the shared *.lvh.me cookie.
 */
test.describe('Tournament bracket', () => {
    test('record a result and attach an image on a bracket match', async ({ page, baseURL }) => {
        const central = new URL(baseURL ?? 'http://lvh.me:8000');
        const clubOrigin = `${central.protocol}//smashclub.${central.host}`;

        await page.goto('/login');
        await page.getByLabel('Email').fill('owner@smashclub.test');
        await page.getByLabel('Password', { exact: true }).fill('password');
        await page.getByRole('button', { name: /log in/i }).click();
        await page.waitForURL((url) => !url.pathname.endsWith('/login'));

        // Open the Open Singles (8-player) bracket from the tournament page.
        await page.goto(`${clubOrigin}/tournaments`);
        await page.getByRole('link', { name: /Summer Slam/ }).click();
        await page
            .locator('li', { hasText: 'Open Singles' })
            .getByRole('link', { name: /View bracket/ })
            .click();
        await expect(page.getByRole('heading', { name: 'Open Singles' })).toBeVisible();

        // Open Ben's quarter-final match.
        await page.getByRole('button').filter({ hasText: 'Ben Okafor' }).first().click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();

        // Attach an image — it appears in the dialog gallery.
        await dialog.locator('input[type="file"]').setInputFiles({ name: 'scorecard.png', mimeType: 'image/png', buffer: PNG });
        await expect(dialog.getByRole('img').first()).toBeVisible();

        // Record Ben as the winner and save.
        await dialog.getByRole('button', { name: 'Ben Okafor', exact: true }).click();
        await dialog.getByLabel('Score').fill('6-4 6-2');
        await dialog.getByRole('button', { name: 'Save match' }).click();

        // Ben advanced to the semi-final → his name now appears on two match cards.
        await expect(page.getByRole('button').filter({ hasText: 'Ben Okafor' })).toHaveCount(2);
    });
});
