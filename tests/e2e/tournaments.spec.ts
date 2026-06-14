import { expect, test } from '@playwright/test';

/**
 * End-to-end: a club owner (who is a club-admin, so has `tournament.manage`) registers a
 * club via the onboarding UI, then creates a tournament and sees it in the list.
 *
 * Mirrors tests/e2e/club-onboarding.spec.ts for the onboarding portion. Draw/scoring are
 * out of scope for this slice, so the test stops once the tournament is listed.
 */
test.describe('Tournaments', () => {
    test('a club-admin can create a tournament and see it listed', async ({ page }) => {
        const id = Date.now().toString(36);
        const slug = `club${id}`;
        const clubName = `Test Club ${id}`;
        const tournamentName = `Spring Open ${id}`;

        // --- Register the club (owner becomes club-admin) ---
        await page.goto('/register-club');
        await page.getByLabel('Club name').fill(clubName);
        await page.locator('#slug').fill(slug);
        await page.getByLabel('Your name').fill('Test Owner');
        await page.getByLabel('Email').fill(`owner-${id}@example.com`);
        await page.getByLabel('Password', { exact: true }).fill('password1234');
        await page.getByLabel('Confirm password').fill('password1234');
        await page.getByRole('button', { name: 'Create club' }).click();

        // Land on the club subdomain dashboard.
        await page.waitForURL(new RegExp(`${slug}\\.lvh\\.me`));

        // --- Create a tournament ---
        await page.goto(`http://${slug}.lvh.me:8000/tournaments`);
        await page.getByRole('link', { name: 'New tournament' }).click();
        await page.waitForURL(/tournaments\/create/);

        await page.getByLabel('Name').fill(tournamentName);
        await page.getByRole('button', { name: 'Create tournament' }).click();

        // Redirected to the tournament's own page, which shows its name.
        await expect(page.getByRole('heading', { name: tournamentName })).toBeVisible();

        // --- It appears in the list ---
        await page.goto(`http://${slug}.lvh.me:8000/tournaments`);
        await expect(page.getByText(tournamentName)).toBeVisible();
    });
});
