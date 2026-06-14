import { expect, test } from '@playwright/test';

/*
 * Platform-admin E2E.
 *
 * The E2E suite has no UI to mint a platform admin (is_platform_admin is set out-of-band,
 * never via the signup form), so a *full* admin walkthrough needs a seeded platform admin
 * (see docs/features/platform-admin.md). Here we assert the negative path that the UI can
 * exercise on its own: a normal (non-admin) club owner is forbidden from /admin/clubs.
 *
 * We first register a club through onboarding — that creates a real club + a normal owner
 * and signs them in — then visit the central admin area and expect a 403.
 */
test.describe('Platform admin guard', () => {
    test('a normal (non-admin) user is forbidden from /admin/clubs', async ({ page }) => {
        const id = Date.now().toString(36);
        const slug = `club${id}`;

        // Register a club → we are now signed in as a normal owner (NOT a platform admin).
        await page.goto('/register-club');
        await page.getByLabel('Club name').fill(`Test Club ${id}`);
        await page.locator('#slug').fill(slug);
        await page.getByLabel('Your name').fill('Test Owner');
        await page.getByLabel('Email').fill(`owner-${id}@example.com`);
        await page.getByLabel('Password', { exact: true }).fill('password1234');
        await page.getByLabel('Confirm password').fill('password1234');
        await page.getByRole('button', { name: 'Create club' }).click();
        await page.waitForURL(new RegExp(`${slug}\\.lvh\\.me`));

        // Hit the central platform-admin area. The session is shared across subdomains,
        // so we arrive authenticated — but as a non-admin, EnsurePlatformAdmin returns 403.
        const response = await page.goto('http://lvh.me:8000/admin/clubs');
        expect(response?.status()).toBe(403);
    });
});
