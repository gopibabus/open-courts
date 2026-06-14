import { expect, test } from '@playwright/test';

test.describe('Club onboarding', () => {
    test('a new club can be registered and the owner lands on its dashboard', async ({ page }) => {
        const id = Date.now().toString(36);
        const slug = `club${id}`;
        const clubName = `Test Club ${id}`;

        await page.goto('/register-club');

        await page.getByLabel('Club name').fill(clubName);
        await page.locator('#slug').fill(slug); // override the auto-suggested slug
        await page.getByLabel('Your name').fill('Test Owner');
        await page.getByLabel('Email').fill(`owner-${id}@example.com`);
        await page.getByLabel('Password', { exact: true }).fill('password1234');
        await page.getByLabel('Confirm password').fill('password1234');

        await page.getByRole('button', { name: 'Create club' }).click();

        // Redirected onto the club's own subdomain…
        await page.waitForURL(new RegExp(`${slug}\\.lvh\\.me`));
        // …and the club dashboard shows the club + the owner's club-admin role.
        await expect(page.getByText(clubName)).toBeVisible();
        await expect(page.getByText('club-admin')).toBeVisible();
    });

    test('rejects an invalid subdomain', async ({ page }) => {
        await page.goto('/register-club');

        await page.getByLabel('Club name').fill('Bad Club');
        await page.locator('#slug').fill('ab'); // too short (min 3)
        await page.getByLabel('Your name').fill('Test Owner');
        await page.getByLabel('Email').fill(`bad-${Date.now()}@example.com`);
        await page.getByLabel('Password', { exact: true }).fill('password1234');
        await page.getByLabel('Confirm password').fill('password1234');

        await page.getByRole('button', { name: 'Create club' }).click();

        await expect(page.getByText(/subdomain|slug/i)).toBeVisible();
        await expect(page).toHaveURL(/register-club/);
    });
});
