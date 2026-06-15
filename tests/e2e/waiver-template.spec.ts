import { expect, test } from '@playwright/test';

/**
 * End-to-end on the seeded demo: an organiser edits the club's waiver template (resets to the
 * defaults, then adds a club-specific clause), saves, and a player then sees that clause on the
 * waiver page — proving the editor → template → player-facing round-trip. Port-agnostic via
 * baseURL; logs in centrally and relies on the shared *.lvh.me cookie.
 */
test.describe('Waiver template', () => {
    const MARKER = 'Players must wear non-marking shoes on every court.';

    test('an organiser edits the template and a player sees the new clause', async ({ page, baseURL }) => {
        const central = new URL(baseURL ?? 'http://lvh.me:8000');
        const clubOrigin = `${central.protocol}//smashclub.${central.host}`;

        await page.goto('/login');
        await page.getByLabel('Email').fill('owner@smashclub.test');
        await page.getByLabel('Password', { exact: true }).fill('password');
        await page.getByRole('button', { name: /log in/i }).click();
        await page.waitForURL((url) => !url.pathname.endsWith('/login'));

        await page.goto(`${clubOrigin}/tournaments/waiver-template`);
        await expect(page.getByRole('heading', { name: 'Waiver template' })).toBeVisible();

        // Reset to a known state (the 4 platform defaults), then add a club-specific clause.
        await page.getByRole('button', { name: /Reset to default/ }).click();
        await page.getByRole('button', { name: /Add clause/ }).click();
        await page.getByRole('textbox', { name: 'Clause 5' }).fill(MARKER);

        // Save and wait for the PUT to land before navigating away (Apache latency-safe).
        const saved = page.waitForResponse(
            (r) => r.url().includes('/tournaments/waiver-template') && r.request().method() === 'PUT',
        );
        await page.getByRole('button', { name: 'Save template' }).click();
        await saved;
        await expect(page.getByText('Saved')).toBeVisible();

        // The player-facing waiver now lists the new clause.
        await page.goto(`${clubOrigin}/tournaments/1/waiver`);
        await expect(page.getByRole('heading', { name: 'Player waiver' })).toBeVisible();
        await expect(page.getByText(MARKER)).toBeVisible();
    });
});
