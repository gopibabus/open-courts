import { expect, test } from '@playwright/test';

/**
 * End-to-end for requirement #1: moving between the dashboard and account settings on a
 * club subdomain is seamless — settings renders inside the SAME club shell (collapsible
 * sidebar with the club nav), not the central app shell. We prove the shell by asserting a
 * club-only nav item ("Bookings") is present on /settings/profile, then round-trip back to
 * the dashboard via the sidebar.
 */
test.describe('Settings shell', () => {
    test('settings renders in the club shell and round-trips to the dashboard', async ({ page }) => {
        const id = Date.now().toString(36);
        const slug = `settings${id}`;

        await page.goto('/register-club');
        await page.getByLabel('Club name').fill(`Settings Club ${id}`);
        await page.locator('#slug').fill(slug);
        await page.getByLabel('Your name').fill('Settings Owner');
        await page.getByLabel('Email').fill(`owner-${id}@example.com`);
        await page.getByLabel('Password', { exact: true }).fill('password1234');
        await page.getByLabel('Confirm password').fill('password1234');
        await page.getByRole('button', { name: 'Create club' }).click();
        await page.waitForURL(new RegExp(`${slug}\\.lvh\\.me`));

        const origin = new URL(page.url()).origin;

        // --- Go to account settings on the club subdomain ---
        await page.goto(`${origin}/settings/profile`);
        await expect(page.getByText('Profile information')).toBeVisible();

        // The club shell is present: a club-only nav item that the central app shell lacks.
        await expect(page.getByRole('link', { name: 'Bookings' })).toBeVisible();
        // And the custom brand logo image (never an inline Laravel SVG mark).
        await expect(page.locator('img[src*="logo"]').first()).toBeVisible();

        // --- Round-trip back to the dashboard via the sidebar ---
        await page.getByRole('link', { name: 'Dashboard' }).click();
        await page.waitForURL(`${origin}/`);
        await expect(page.getByRole('heading', { name: 'Dashboard', exact: true })).toBeVisible();
    });
});
