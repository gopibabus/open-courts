import { expect, test } from '@playwright/test';

// 0 = Monday .. 6 = Sunday — matches the day_of_week select in the availability editor.
const DAY_LABELS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

/** Map a JS Date (Sun=0..Sat=6) to the app's day_of_week (Mon=0..Sun=6). */
function domainDow(date: Date): number {
    return (date.getDay() + 6) % 7;
}

/** YYYY-MM-DD for a local date (the value a native <input type=date> expects). */
function isoDate(date: Date): string {
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

test.describe('Court booking', () => {
    test('a club admin can register, create a bookable court, book a slot, and see it', async ({ page }) => {
        const id = Date.now().toString(36);
        const slug = `book${id}`;
        const clubName = `Booking Club ${id}`;

        // Book three days out so the slot is comfortably in the future.
        const bookDate = new Date();
        bookDate.setDate(bookDate.getDate() + 3);
        const dateValue = isoDate(bookDate);
        const dayLabel = DAY_LABELS[domainDow(bookDate)];

        // 1. Register a club via the onboarding UI (the registrant is club-admin, who has
        //    every permission — including court.book and court.manage).
        await page.goto('/register-club');
        await page.getByLabel('Club name').fill(clubName);
        await page.locator('#slug').fill(slug);
        await page.getByLabel('Your name').fill('Booking Owner');
        await page.getByLabel('Email').fill(`owner-${id}@example.com`);
        await page.getByLabel('Password', { exact: true }).fill('password1234');
        await page.getByLabel('Confirm password').fill('password1234');
        await page.getByRole('button', { name: 'Create club' }).click();

        await page.waitForURL(new RegExp(`${slug}\\.lvh\\.me`));

        // 2. Create a court.
        await page.goto(`http://${slug}.lvh.me:8000/courts`);
        await expect(page.getByRole('heading', { name: 'Courts' })).toBeVisible();

        const courtName = `Show Court ${id}`;
        await page.getByRole('button', { name: 'New court' }).click();
        await page.getByLabel('Name').fill(courtName);
        await page.getByRole('button', { name: 'Create court' }).click();
        await expect(page.getByText(courtName)).toBeVisible();

        // 3. Give the court an availability window on the booking day, 08:00–20:00.
        await page.getByRole('button', { name: 'Edit availability' }).click();
        await page.getByRole('button', { name: 'Add window' }).click();

        // The day Select defaults to "Mon"; switch it to the booking weekday.
        const daySelect = page.getByRole('combobox').first();
        await daySelect.click();
        await page.getByRole('option', { name: dayLabel, exact: true }).click();

        // Two time inputs (opens / closes) in the row.
        const timeInputs = page.locator('input[type="time"]');
        await timeInputs.nth(0).fill('08:00');
        await timeInputs.nth(1).fill('20:00');
        await page.getByRole('button', { name: 'Save schedule' }).click();

        // 4. Go to the booking screen, pick the court + date, book a slot.
        await page.goto(`http://${slug}.lvh.me:8000/bookings`);
        await expect(page.getByRole('heading', { name: 'Book a court' })).toBeVisible();

        await page.getByLabel('Date').fill(dateValue);

        // The court is the first (only) option and is selected by default. Book the 10:00 slot.
        await page.getByRole('button', { name: 'Book 10:00' }).click();

        // 5. It shows up under "My bookings" with a Cancel action.
        await expect(page.getByRole('heading', { name: 'My bookings' })).toBeVisible();
        const myBookings = page.locator('section', { has: page.getByRole('heading', { name: 'My bookings' }) });
        await expect(myBookings.getByText(courtName)).toBeVisible();
        await expect(myBookings.getByRole('button', { name: 'Cancel' })).toBeVisible();
    });
});
