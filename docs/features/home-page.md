# Home page

The public landing page served at `/` on the **central domain** (route name `home`,
rendered by `routes/web.php` → Inertia `welcome`). It's the front door for a community
thinking about putting their courts online.

- **Component:** [`resources/js/pages/welcome.tsx`](../../resources/js/pages/welcome.tsx)
- **Guard test:** [`tests/Feature/HomePageTest.php`](../../tests/Feature/HomePageTest.php) — renders for guests, resolves to the `welcome` component, never redirects to login.

## Voice

Warm and human — written for **the people in a community who just want to get on court**
(residents, club members, the neighbour who organises the Saturday social), not for operators
or engineers. No jargon: "book a court", "round up the neighbours", "house rules". Tone reference
is yourcourts.com — friendly, simple, reassuring.

## Look

Uses the project design system (see [`docs/ui/design-system.md`](../ui/design-system.md)):

- **Monochrome only**, via semantic tokens (`bg-background`, `text-foreground`,
  `text-muted-foreground`, `bg-card`, `bg-muted`, `bg-accent`, `border-border`). Colour is
  reserved for state (focus `ring-ring`, the closed-for-upkeep cell `ring-destructive`).
- **JetBrains Mono** throughout. The dot-matrix **Doto** face (`.text-display`) is used **only**
  for numerals and the `OPEN·TENNIS` wordmark (court numbers, times, the `6—4 7—5` score, the
  big "why neighbours love it" figures).
- **Light / dark / system** via a segmented toggle of **sun / moon / monitor icons** (each button
  keeps an `aria-label` since the icon carries no text); reused in the header and footer.

## What's shown (and the rules behind it)

- **Every feature is a buildable, screenshot-free mock** made of divs + tokens — a live court
  board, a dot-matrix scoreboard, an event sign-up card, a team roster, a roles grid, faux
  subdomain address bars, and a "house rules" card. They stay in sync with the theme and render
  in both light and dark for free.
- The **roles grid mirrors the real seeder** ([`RolePermissionSeeder::roleMatrix()`](../../database/seeders/RolePermissionSeeder.php)),
  shown with friendly ability names: **Organiser** can do everything (club-admin); **Coach** can
  Book a court / Run events / Make teams (`court.book` + `tournament.manage` + `team.manage`);
  **Member** can Book a court (`court.book`). Keep the grants in sync if the matrix changes.
- **Honest:** no fabricated pricing, customer logos, or invented metrics. The "why neighbours
  love it" figures (`30s` to book, `0` double-bookings, `24/7`, `1 tap`) describe how it feels to
  use, not made-up averages.
- **Central routes only.** CTAs point at `register-club.create`, `login`, `register`,
  `ui.gallery`, `home`. Tenant routes (bookings/tournaments/teams/members) don't exist on the
  central domain and would throw in Ziggy — in-page jumps use `<a href="#id">`, route targets use
  Inertia `<Link>`.

## Section order

header → hero (+ live court board) → How it works → Book a court → Play & compete (event card +
scoreboard) → Your people (teams + roles) → Your club online (subdomains) → The old way vs the
OpenTennis way (comparison) → Why neighbours love it (stats) → For whoever runs it (house rules)
→ Good questions (FAQ, native `<details>`) → closing call-to-action → footer.

## Accessibility

Native `<details>`/`<summary>` FAQ, theme-toggle buttons expose `aria-label` + `aria-pressed`
(icon is `aria-hidden`), the roles grid icons carry `aria-label` ("yes" / "no") so it isn't
colour-only, the decorative court board has a descriptive `aria-label`, and the wide tables sit
in `overflow-x-auto` wrappers for small screens.
