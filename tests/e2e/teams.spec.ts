import { expect, test } from '@playwright/test';

/**
 * End-to-end: a club owner (a club-admin, so they have `team.manage`) registers a club via
 * the onboarding UI, then creates a team and sees it listed.
 *
 * Mirrors tests/e2e/courts.spec.ts for the onboarding portion. Roster management beyond
 * creation is covered by the Pest feature test; this E2E stops once the team is listed.
 */
test.describe('Teams', () => {
    test('a club-admin can create a team and see it listed', async ({ page }) => {
        const id = Date.now().toString(36);
        const slug = `teams${id}`;
        const clubName = `Teams Club ${id}`;
        const teamName = `First VII ${id}`;

        // --- Register the club (owner becomes club-admin) ---
        await page.goto('/register-club');
        await page.getByLabel('Club name').fill(clubName);
        await page.locator('#slug').fill(slug);
        await page.getByLabel('Your name').fill('Team Owner');
        await page.getByLabel('Email').fill(`owner-${id}@example.com`);
        await page.getByLabel('Password', { exact: true }).fill('password1234');
        await page.getByLabel('Confirm password').fill('password1234');
        await page.getByRole('button', { name: 'Create club' }).click();

        // Land on the club subdomain dashboard.
        await page.waitForURL(new RegExp(`${slug}\\.lvh\\.me`));

        // --- Create a team ---
        await page.goto(`${new URL(page.url()).origin}/teams`);
        await expect(page.getByRole('heading', { name: 'Teams' })).toBeVisible();

        await page.getByRole('button', { name: 'New team' }).click();
        await page.getByLabel('Name').fill(teamName);
        await page.getByRole('button', { name: 'Create team' }).click();

        // Redirected to the team's own page, which shows its name.
        await expect(page.getByRole('heading', { name: teamName })).toBeVisible();

        // --- It appears in the list ---
        await page.goto(`${new URL(page.url()).origin}/teams`);
        await expect(page.getByText(teamName)).toBeVisible();
    });
});
