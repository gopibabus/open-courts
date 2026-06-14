import { expect, test } from '@playwright/test';

/**
 * End-to-end: a signed-in club member opens the Help page from the topbar and files a
 * support request. The default topic is fine, so we only fill subject + message and
 * submit, then assert the in-app confirmation appears.
 */
test.describe('Help', () => {
    test('a member can submit a support request', async ({ page }) => {
        const id = Date.now().toString(36);
        const slug = `help${id}`;

        // --- Register a club so we have an authenticated member on a club subdomain ---
        await page.goto('/register-club');
        await page.getByLabel('Club name').fill(`Help Club ${id}`);
        await page.locator('#slug').fill(slug);
        await page.getByLabel('Your name').fill('Help Owner');
        await page.getByLabel('Email').fill(`owner-${id}@example.com`);
        await page.getByLabel('Password', { exact: true }).fill('password1234');
        await page.getByLabel('Confirm password').fill('password1234');
        await page.getByRole('button', { name: 'Create club' }).click();
        await page.waitForURL(new RegExp(`${slug}\\.lvh\\.me`));

        // --- Open Help from the topbar link ---
        await page.getByRole('link', { name: 'Help' }).click();
        await expect(page.getByRole('heading', { name: 'Help & support' })).toBeVisible();

        // --- Fill and submit the support form (default topic is fine) ---
        await page.getByLabel('Subject').fill('Floodlights on court 2');
        await page.getByLabel('Message').fill('The lights on court 2 do not switch on in the evening.');
        await page.getByRole('button', { name: 'Send request' }).click();

        // --- The in-app confirmation appears ---
        await expect(page.getByText(/received your request/i)).toBeVisible();
    });
});
