import { expect, test } from '@playwright/test';

test.describe('Members & invitations', () => {
    test('an admin can invite a member and see them in the pending list', async ({ page }) => {
        const id = Date.now().toString(36);
        const slug = `club${id}`;
        const clubName = `Test Club ${id}`;
        const inviteEmail = `invitee-${id}@example.com`;

        // 1. Register a club via the onboarding UI (mirrors club-onboarding.spec.ts).
        await page.goto('/register-club');

        await page.getByLabel('Club name').fill(clubName);
        await page.locator('#slug').fill(slug);
        await page.getByLabel('Your name').fill('Test Owner');
        await page.getByLabel('Email').fill(`owner-${id}@example.com`);
        await page.getByLabel('Password', { exact: true }).fill('password1234');
        await page.getByLabel('Confirm password').fill('password1234');

        await page.getByRole('button', { name: 'Create club' }).click();

        // Redirected onto the club's own subdomain dashboard.
        await page.waitForURL(new RegExp(`${slug}\\.lvh\\.me`));

        // 2. Go to the members page on the club subdomain.
        await page.goto(`http://${slug}.lvh.me:8000/members`);
        await expect(page.getByRole('heading', { name: 'Members' })).toBeVisible();

        // 3. Send an invite.
        await page.getByRole('button', { name: 'Invite member' }).click();
        await page.getByLabel('Email').fill(inviteEmail);
        await page.getByRole('button', { name: 'Send invite' }).click();

        // 4. The invite appears in the pending list.
        await expect(page.getByText(inviteEmail)).toBeVisible();
    });
});
