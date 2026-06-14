import { expect, test } from '@playwright/test';

/**
 * End-to-end: a club owner (a club-admin, so they have `tournament.manage` + `team.manage`)
 * registers a club, creates a tournament, then creates a team UNDER that tournament — teams
 * are specific to a tournament. The one-team-per-tournament rule and the EC (management) are
 * covered by the Pest feature tests; this E2E verifies the create-team-under-a-tournament flow.
 */
test.describe('Teams', () => {
    test('a club-admin can create a team under a tournament', async ({ page }) => {
        const id = Date.now().toString(36);
        const slug = `teams${id}`;
        const clubName = `Teams Club ${id}`;
        const tournamentName = `Cup ${id}`;
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
        await page.waitForURL(new RegExp(`${slug}\\.lvh\\.me`));

        const origin = new URL(page.url()).origin;

        // --- A team belongs to a tournament, so create one first ---
        await page.goto(`${origin}/tournaments`);
        await page.getByRole('link', { name: 'New tournament' }).click();
        await page.waitForURL(/tournaments\/create/);
        await page.getByLabel('Name').fill(tournamentName);
        await page.getByRole('button', { name: 'Create tournament' }).click();
        await expect(page.getByRole('heading', { name: tournamentName })).toBeVisible();

        // --- Create a team from the tournament's page ---
        await page.getByRole('button', { name: 'New team' }).click();
        await page.getByLabel('Name').fill(teamName);
        await page.getByRole('button', { name: 'Create team' }).click();

        // Redirected to the team's roster page, which shows its name + its tournament.
        await expect(page.getByRole('heading', { name: teamName })).toBeVisible();
        await expect(page.getByText(tournamentName).first()).toBeVisible();

        // --- And appears under its tournament ---
        await page.getByRole('link', { name: tournamentName }).click();
        await expect(page.getByText(teamName)).toBeVisible();
    });
});
