import { expect, test } from '@playwright/test';

test.describe('Courts management', () => {
    test('a club admin can create a court and see it listed', async ({ page }) => {
        const id = Date.now().toString(36);
        const slug = `courts${id}`;
        const clubName = `Courts Club ${id}`;

        // Register a club via the onboarding UI (mirrors club-onboarding.spec.ts).
        await page.goto('/register-club');
        await page.getByLabel('Club name').fill(clubName);
        await page.locator('#slug').fill(slug);
        await page.getByLabel('Your name').fill('Court Owner');
        await page.getByLabel('Email').fill(`owner-${id}@example.com`);
        await page.getByLabel('Password', { exact: true }).fill('password1234');
        await page.getByLabel('Confirm password').fill('password1234');
        await page.getByRole('button', { name: 'Create club' }).click();

        // Landed on the club's own subdomain.
        await page.waitForURL(new RegExp(`${slug}\\.lvh\\.me`));

        // Navigate to the courts page (same subdomain origin).
        await page.goto(`http://${slug}.lvh.me:8000/courts`);
        await expect(page.getByRole('heading', { name: 'Courts' })).toBeVisible();

        // Create a court via the dialog.
        const courtName = `Centre Court ${id}`;
        await page.getByRole('button', { name: 'New court' }).click();
        await page.getByLabel('Name').fill(courtName);
        await page.getByRole('button', { name: 'Create court' }).click();

        // The new court appears in the list.
        await expect(page.getByText(courtName)).toBeVisible();
    });
});
