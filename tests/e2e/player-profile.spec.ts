import { expect, test } from '@playwright/test';

/**
 * End-to-end: a signed-in club member opens a fellow member's player profile and sees their
 * competitive record + trophy case. Uses the seeded demo club (Smash Tennis Club), where a
 * finished Men's Singles draw makes Ben Okafor the champion.
 *
 * Port-agnostic: the club origin is derived from baseURL. We log in on the central domain
 * (same-origin redirect) and rely on the shared *.lvh.me session cookie to reach the club.
 */
test.describe('Player profile', () => {
    test('a member can view a player profile with a championship trophy', async ({ page, baseURL }) => {
        const central = new URL(baseURL ?? 'http://lvh.me:8000');
        const clubOrigin = `${central.protocol}//smashclub.${central.host}`;

        // Sign in as a demo club member (all demo passwords are "password").
        await page.goto('/login');
        await page.getByLabel('Email').fill('owner@smashclub.test');
        await page.getByLabel('Password', { exact: true }).fill('password');
        await page.getByRole('button', { name: /log in/i }).click();
        await page.waitForURL((url) => !url.pathname.endsWith('/login'));

        // The shared cookie authenticates us on the club subdomain.
        await page.goto(`${clubOrigin}/members`);
        await page.getByRole('link', { name: 'Ben Okafor' }).click();

        await expect(page.getByRole('heading', { name: 'Ben Okafor' })).toBeVisible();
        // Trophy case shows a Champion placement; the record shows a title.
        await expect(page.getByText('Champion').first()).toBeVisible();
        await expect(page.getByText('Titles')).toBeVisible();
    });
});
