# Club dashboard & workspace shell

After a member signs in on a club subdomain (`<club>.<central>`), they land on the
**club dashboard** — the workspace home — inside a consistent sidebar + topbar shell
that wraps every authenticated club page.

## The shell

- **[`resources/js/layouts/club-layout.tsx`](../../resources/js/layouts/club-layout.tsx)** — `ClubLayout`.
  Composes the shadcn sidebar kit (`AppShell` → `SidebarProvider`) with:
  - **[`club-sidebar.tsx`](../../resources/js/components/club/club-sidebar.tsx)** — brand, the club's
    sections (Dashboard · Bookings · Courts · Tournaments · Teams · Members), and the club identity.
    Nav targets are tenant routes; active state is matched on the current path.
  - a sticky **topbar** — page title, a search placeholder, the icon [`ThemeToggle`](../../resources/js/components/theme-toggle.tsx)
    (sun/moon/monitor), a Help link, and the user menu (settings, log out).

  Use it on any club page: `<ClubLayout title="Courts"> …page content… </ClubLayout>`. It renders
  `<Head title>` for you and provides the page padding.

- The active **club** is shared on every tenant request by
  [`HandleInertiaRequests`](../../app/Http/Middleware/HandleInertiaRequests.php) (`props.club`,
  `null` on the central domain), so the shell always has the club name without per-controller plumbing.

## The dashboard

`route('tenant.dashboard')` (`/` on a club subdomain) →
[`DashboardController`](../../app/Http/Controllers/Tenant/DashboardController.php). It is a **read-only
aggregation** across the booking, facilities, tournaments and membership contexts — all automatically
tenant-scoped by `BelongsToTenant` — plus a `capabilities` map from `$user->can(...)`. No domain Action
is involved because nothing is mutated.

[`tenant/dashboard.tsx`](../../resources/js/pages/tenant/dashboard.tsx) renders, on real data:

- **Quick actions** (permission-gated): book a court, invite member, new tournament, new team, add court.
- **Stat tiles**: members, courts, tournaments, teams — each links to its section.
- **Charts** (hand-built SVG/divs, no chart library, monochrome with `.text-display` numerals): a
  court-usage donut, bookings-this-month trend, bookings-this-week stacked by court, a busiest-days
  heatmap, a bookings-this-year line, busiest-courts bars, a tournament spotlight, and upcoming-bookings
  + recent-members lists.
- **Empty states**: a brand-new club shows zeros and prompts (e.g. "Set court hours", "Start one").

### Gotchas baked in here

- **Carbon 3 `diffInMinutes` is signed** (`$b - $a`), unlike Carbon 2's absolute value. Court-capacity
  and booking-duration sums wrap durations in `abs()` — without it, capacity went negative and the
  donut read 0%.
- Datetimes in the "upcoming bookings" list are serialized as **naive wall-clock** (`Y-m-d\TH:i:s`, no
  offset), the same rule as `BookingController`, so the browser renders the club-local hour.

## Demo data

`DemoSeeder` provisions a lively club (Smash Tennis Club, `smashclub`): 6 members with roles, 3 courts
with opening hours, ~36 bookings across the week/month/year, and an open tournament with two categories,
six entrants and two squads — enough for the dashboard charts to have shape. Sign in at
`smashclub.<central>` as `owner@smashclub.test` / `password`.
