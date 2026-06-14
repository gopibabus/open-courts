# Marketing home page

The public landing page served at `/` on the **central domain** (route name `home`,
rendered by `routes/web.php` → Inertia `welcome`). It is the front door for prospective
club operators — it sells what OpenTennis delivers and routes them to sign up.

- **Component:** [`resources/js/pages/welcome.tsx`](../../resources/js/pages/welcome.tsx)
- **Guard test:** [`tests/Feature/HomePageTest.php`](../../tests/Feature/HomePageTest.php) — renders for guests, resolves to the `welcome` component, never redirects to login.

## Design

Aesthetic is the project design system (see [`docs/ui/design-system.md`](../ui/design-system.md)),
inspired by **vask.dev**'s developer-documentation look:

- **Monochrome only**, via semantic tokens (`bg-background`, `text-foreground`,
  `text-muted-foreground`, `bg-card`, `bg-muted`, `bg-accent`, `border-border`). Color is
  reserved for state (focus `ring-ring`, blackout cell `ring-destructive`, suspended badge).
- **JetBrains Mono** everywhere. The dot-matrix **Doto** face (`.text-display`) is used
  **only** for numerals and the `OPEN·TENNIS` wordmark (court numbers, times, scores, counts,
  the stats band) — never for headings, body, nav, or buttons.
- **`FIG.0x` / `FEAT.0x`** monospace documentation eyebrows label each section, with hairline
  `border-border` dividers carrying the structure.
- Light / dark / system via the `useAppearance()` segmented toggle (header + footer).

## Self-imposed rules (enforced by review)

- **Every capability is shown with a buildable, div-only mock — no screenshots.** Court board,
  dot-matrix scoreboard, tournament draw card, roster, **roles matrix**, platform console, and
  faux subdomain address bars are all CSS + tokens, so they stay in sync with the theme and
  render in both light and dark for free.
- **The roles matrix mirrors the real seeder** (`RolePermissionSeeder::roleMatrix()`): Owner/
  Club-admin = all seven permissions; Coach = `court.book` + `tournament.manage` + `team.manage`;
  Member = `court.book`. Keep them in sync if the matrix changes.
- **Honesty:** no fabricated pricing tiers, customer logos, or invented uptime/usage numbers.
  Stat figures (`00` double-bookings, `24/7`, `1` subdomain, `100%` attributed) are properties
  of how the app is built, not marketing averages.
- **Central routes only.** CTAs use `register-club.create`, `login`, `register`, `ui.gallery`,
  `home`. Tenant routes (bookings/tournaments/teams/members) **do not exist on the central
  domain** and would throw in Ziggy — in-page jumps use `<a href="#id">`, route targets use
  Inertia `<Link>`.

## Section order

nav → hero (+ live court board) → `FIG.01` how-it-works → `FEAT.01` booking → `FEAT.02`
tournaments → `FEAT.03/04` teams & roles → `FEAT.05` subdomains & isolation → `FIG.02`
comparison table → `FIG.03` stats band → `FEAT.06` platform & engineering → `FIG.04` FAQ
(native `<details>`) → inverted CTA band → multi-column footer.

## Accessibility

`<details>`/`<summary>` FAQ (keyboard-native), theme toggle exposes `aria-pressed`, the roles
matrix icons carry `aria-label` ("granted" / "not granted") so grant/deny isn't color-only, the
decorative court-board grid has a descriptive `aria-label`, and wide tables sit in
`overflow-x-auto` wrappers for mobile.
