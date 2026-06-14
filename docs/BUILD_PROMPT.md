# OpenTennis — Master Build Prompt

> Paste this into a Claude Code session opened at the repo root. It is written for an
> autonomous agent with full permissions. Execute it end-to-end; do not stop until every
> feature ships with passing end-to-end tests and is documented.

---

## 0. Your role

You are a **ruthless principal software architect and full-stack engineer**. You have full
permissions and full autonomy. Make decisions; don't ask for hand-holding. When a choice has
a clearly correct answer for a multi-tenant SaaS, take it and record *why* in an ADR. Favor
boring, proven, well-bounded designs over cleverness. Delete code that doesn't earn its place.

You are **evolving an existing skeleton**, not starting from zero. The repo already contains a
Laravel 12 + Inertia/React (TypeScript) app with `stancl/tenancy` (single-database, subdomain)
and `spatie/laravel-permission` (roles scoped per tenant), a working demo seed, Docker, and a
passing test suite. Read `README.md` and `CLAUDE.md` first. Preserve what holds up; refactor
what doesn't toward the target architecture below.

## 1. Product

A **one-stop platform for tennis players and clubs** — book courts and run tournaments — sold
as a multi-tenant SaaS, in the spirit of yourcourts.com and ioncourt.com. **A tenant is a club
/ facility.** Players discover a club, book courts, join leagues, and compete in tournaments;
club staff manage courts, pricing, members, and events; the platform operator manages clubs
and billing.

## 2. Operating principles

- **Autonomy with a paper trail.** Decide, implement, and document. Every non-obvious decision
  becomes an ADR in `docs/adr/NNNN-title.md` (context → decision → consequences).
- **Definition of Done (per feature):** domain logic + UI + authorization + **end-to-end tests
  green** + documentation (with diagrams) + ADRs updated. A feature is not "done" until its E2E
  suite passes inside Docker.
- **Recursive verification.** After each feature, run the *entire* test suite, not just the new
  tests. Fix regressions before moving on. Loop until the full suite is green.
- **Vertical slices.** Ship one bounded context end-to-end (DB → domain → API/Inertia → UI →
  tests → docs) before starting the next. No half-wired layers.

## 3. Tech stack (pin to current stable)

- **PHP 8.4**, **Laravel 12** (current stable). **PostgreSQL 17** as the runtime database.
- **React 19 + TypeScript + Inertia v2 + Vite**; **Tailwind CSS v4**; **shadcn/ui** for *all*
  components (see §8).
- **Queues/realtime/infra:** Redis (cache + queue), **Laravel Horizon** (queue dashboard),
  **Laravel Reverb** (WebSockets for live availability, scores, notifications).
- **Testing:** **Pest** (unit/feature), **Playwright** (browser E2E). **Laravel Pint** (style),
  **PHPStan/Larastan** (static analysis, max level you can keep green).

### Package policy (resolve the "core-first" rule explicitly)

1. **First choice: official Laravel packages.** Fortify (auth flows), Sanctum (SPA/API auth),
   Cashier–Stripe (club subscriptions), Horizon, Reverb, Scout (search), Pennant (feature
   flags), Telescope (local debug), Pint, Pest.
2. **Second choice: a reputable, actively-maintained community package** *only* where Laravel
   has no first-party answer — namely **`stancl/tenancy`** (multi-tenancy) and
   **`spatie/laravel-permission`** (RBAC). Keep these.
3. **Last resort: build it.** Justify in an ADR.

Never reinvent something that (1) or (2) already covers well.

## 4. Architecture — Domain-Driven Design + events

Restructure the app into **bounded contexts as modules** under `app/Domains/<Context>/`. Each
context owns its models (aggregates/entities), value objects, application **Actions** (use-case
commands), domain **Events**, **Listeners/Projectors**, **Policies**, **DTOs**, and read
queries. Controllers are thin: validate → dispatch an Action → return an Inertia response.

**Bounded contexts:**

- **Identity** — users, authentication (Fortify), profiles, platform-admin flag.
- **Tenancy** — clubs (tenants), domains, club settings, provisioning.
- **Membership** — club ↔ user membership, invitations, per-club roles/permissions.
- **Facilities** — courts, surfaces, availability windows, blackout dates.
- **Booking** — reservations, pricing rules, conflict-free scheduling, cancellation, check-in.
- **Tournaments** — events, categories/divisions, registration, seeding, draws/brackets,
  match scheduling onto courts, score entry, standings, results.
- **Billing** — club subscription plans (Cashier/Stripe), invoices; optional pay-per-booking.
- **Notifications** — email + realtime (Reverb) for confirmations, reminders, schedules.

**Event-driven rules:**

- Model meaningful state transitions as **domain events** (e.g. `BookingConfirmed`,
  `BookingCancelled`, `TournamentRegistrationOpened`, `DrawGenerated`, `MatchScored`,
  `MatchCompleted`, `SubscriptionStarted`). Maintain an **event catalog** in
  `docs/events/event-catalog.md`.
- **Side effects live in queued listeners** (notifications, projections, standings
  recalculation, court-schedule rebuilds) — never inline in controllers/Actions.
- Use an **outbox/transactional dispatch** pattern so events are only emitted after the writing
  transaction commits. Listeners must be **idempotent**.
- For Booking and Tournament read models (calendars, brackets, standings), build **projections**
  updated by listeners (CQRS-lite). Do **not** adopt full event sourcing; use events +
  projections where they earn their keep, and document the boundary in an ADR.

## 5. Multi-tenancy (keep the skeleton's model)

- **Single PostgreSQL database, row-level isolation.** Tenant-owned tables carry `tenant_id`;
  the `BelongsToTenant` trait + global scope enforce isolation. Never reintroduce per-tenant
  databases or the `DatabaseTenancyBootstrapper`.
- **Subdomain identification.** `localhost` / apex = central (marketing, signup, platform
  admin); `<club>.<central>` = the club app. Central and tenant routes are domain-constrained
  so they don't shadow each other.
- **Authorization = two axes:** per-club roles via spatie teams (`team_id == tenant_id`);
  platform super-admin via the `users.is_platform_admin` flag + `Gate::before`. Every state-
  changing action goes through a **Policy**. Treat cross-tenant data access as a critical bug —
  add tests that prove isolation.

## 6. Database schema — DB-neutral via Eloquent, Postgres at runtime

The schema must be **portable across databases** because everything goes through Eloquent.
Postgres is the runtime, but **do not** couple the schema to Postgres.

- Use **only the Schema builder's portable column types** (`uuid`, `string`, `text`, `integer`,
  `bigInteger`, `decimal`, `boolean`, `date`, `dateTime`/`timestamp`, `json`). **No raw
  vendor-specific DDL**, no Postgres native `enum`/array/`hstore`/`jsonb`-only features in
  migrations.
- **UUID (string) primary keys** everywhere (DB-neutral, non-guessable, multi-tenant friendly).
- **Enums** live in PHP (native enum + Eloquent cast) backed by a `string` column — not DB enums.
- **Money** as integer **minor units** (e.g. cents) with an explicit currency code; never float.
- **All timestamps UTC**; store club timezone on the tenant and convert at the edges.
- Add proper indexes and **DB-level guards against double-booking** expressed portably (unique
  constraints / overlap checks enforced in the Booking aggregate within a transaction with row
  locking; document the concurrency strategy in an ADR).
- Produce an **ERD** (`docs/db/erd.md`, Mermaid `erDiagram`) covering at least: clubs, users,
  memberships, roles/permissions, courts, court_availability, blackouts, pricing_rules,
  bookings, tournaments, categories, registrations, teams, players, draws, matches, scores,
  plans, subscriptions, invoices, notifications.

## 7. Feature roadmap (build in this order; each is a vertical slice with E2E tests)

1. **Club onboarding** — public signup provisions a tenant + owner + default roles; club
   settings (name, timezone, branding).
2. **Members & roles** — invite members, accept invites, assign per-club roles; member directory.
3. **Courts & availability** — CRUD courts (surface, indoor/outdoor), weekly availability
   windows, blackout dates.
4. **Booking** — browse a court calendar, reserve a slot, **conflict-free** (no double-booking
   under concurrency), pricing, cancellation policy, check-in; "my bookings".
5. **Pricing & billing** — club subscription plans via Cashier/Stripe; optional pay-per-booking.
6. **Tournaments** — create event, define categories (singles/doubles/mixed; age/skill),
   open registration, seed entrants, **generate draws** (single & double elimination,
   round-robin, groups→knockout), schedule matches onto courts, enter scores, compute
   standings, publish results + bracket view.
7. **Teams & rosters** — team registration for team events; roster management.
8. **Notifications & realtime** — email + Reverb push for booking confirmations/reminders and
   live match schedules/scores.
9. **Platform admin** — manage clubs/plans, impersonation, cross-tenant ops (super-admin only).

For **every** feature: model the aggregate, emit domain events, enforce policies, build the
shadcn UI, and write **Playwright E2E tests** plus Pest feature tests that prove the happy path,
the authorization rules, the tenant-isolation invariant, and the key edge cases (e.g. booking
conflicts, draw correctness for odd entrant counts, cancellation windows).

## 8. Frontend & design system (shadcn + Tailwind, monochrome, Nothing/vask/Twenty)

**Deliver the UI wired into the real Inertia/React app** (no throwaway mockups). Build **all**
components with **shadcn/ui + Tailwind v4**. The look is **stark monochrome** — black & white,
structured, content-first — drawing from **Nothing (nothing.tech)**, **vask.dev**, and
**Twenty CMS**.

**Theme:** ship **both light and dark** with a persisted **toggle** (system / light / dark).
Anchor everything on a neutral black–white ramp; color is reserved for state (focus, error,
success) and used sparingly.

**Typography:** **JetBrains Mono** as the primary/UI typeface (mono everywhere), plus a
**dot-matrix / pixel display font** (Nothing-style "Ndot/dot-matrix") for highlight moments —
big numerals and headline accents like **scores, court numbers, countdowns, bracket seeds, and
hero headings**. Use the pixel font deliberately and rarely; JetBrains Mono carries the system.

**Design tokens** (semantic, never raw hex in components) — seed from `DESIGN.md`:

- Type scale (compact, Twenty/Linear-like): `xs 11 · sm 12 · md 13 · lg 14 · xl 15 · 2xl 16 ·
  3xl 17 · 4xl 18`; base 16/24, weight 400. Display headings use the pixel font at larger sizes.
- Spacing: `4 · 8 · 10 · 12 · 14 · 16 · 20 · 24`.
- Motion: `instant 150ms · fast 200ms · normal 300ms`.
- Neutral ramp anchored on `oklch` (text primary ≈ `oklch(0.1 0 0)`, secondary ≈
  `oklch(0.45 0 0)`, borders ≈ `oklch(0.85 0 0)`; dark surface `#000`, muted `#272727`). Define
  the mirror-image ramp for light mode. Expose all as CSS variables consumed by Tailwind theme.

**Component rules (enforced):**

- Every interactive component **must** define: default, hover, focus-visible, active, disabled,
  loading, and error states.
- Document keyboard, pointer, and touch behavior; handle long content, overflow, and empty
  states. No one-off spacing/typography exceptions — tokens only.
- **Accessibility: WCAG 2.2 AA**, keyboard-first, visible focus rings, sufficient contrast in
  both themes. Acceptance criteria must be **testable** (assert in Playwright/axe).

Maintain a **living component/design-system doc** at `docs/ui/design-system.md` and, where
helpful, a Storybook-style gallery route in the app. Reuse the DESIGN.md authoring workflow and
QA gates.

## 9. Docker & how to run

- **All Docker config lives in `docker/`** at the repo root. A single root **`docker-compose.yml`**
  brings up every service.
- **`app` service serves Laravel via Apache** (mod_php), runs migrations on boot, and runs the
  scheduler + a queue worker (supervisord). Required services: **`app` (Apache+PHP)** and
  **`db` (PostgreSQL 17)**. Add the supporting services the architecture needs: **`redis`**,
  a **`horizon`/worker**, **`reverb`** (websockets), **`vite`/node** (asset build/dev), and
  **`mailpit`** (email capture in dev).
- `docker compose up --build` must yield a working app at the configured host port, with club
  subdomains resolving (`<club>.localhost`). Document any `/etc/hosts` needs.
- The E2E suite **must run inside Docker** (a `playwright` service or a documented compose
  command), so "it works on my machine" can't hide failures.

## 10. Testing strategy

- **Write E2E tests for each feature** (Playwright) plus Pest feature/unit tests. Drive the real
  app through real flows: signup → book a court → run a tournament → enter scores.
- After every feature, **recursively run the full suite** (Pest + Playwright + Pint + static
  analysis) and fix everything before continuing. The build is green or the feature isn't done.
- Cover the invariants explicitly: **tenant isolation**, **no double-booking under concurrency**,
  **authorization per policy**, **draw/standings correctness**, **money precision**, **timezone
  correctness**.

## 11. Documentation (self-document everything, in plain English + diagrams)

Maintain `docs/` as you build — not at the end:

- `docs/architecture/overview.md` — system context + bounded-context map (Mermaid C4/flowcharts).
- `docs/db/erd.md` — ERD (Mermaid `erDiagram`) + table dictionary.
- `docs/events/event-catalog.md` — every domain event, payload, producers, listeners.
- `docs/features/<feature>.md` — plain-English description + **flow/sequence diagrams** (Mermaid)
  + UI screenshots + the feature's acceptance criteria.
- `docs/ui/design-system.md` — tokens, fonts, theming, component rules, a11y criteria.
- `docs/adr/NNNN-*.md` — decisions (Postgres-but-DB-neutral, events-not-event-sourcing,
  concurrency strategy, package choices, etc.).
- Keep `README.md` and `CLAUDE.md` current.

Write for a competent developer who is new to the project. Prefer simple English and diagrams
over prose walls.

## 12. Guardrails / invariants (non-negotiable)

- Schema stays **DB-neutral** (Eloquent/Schema builder only; no vendor DDL).
- **No cross-tenant data leakage**, ever — prove it with tests.
- **No double-booking** — enforce at the aggregate + DB level, test under concurrency.
- Side effects only in **idempotent, queued listeners**; events dispatched **after commit**.
- Every state change is **authorized by a Policy**; money is integer minor units; times are UTC.
- All UI is **shadcn + Tailwind**, monochrome, dual-theme, JetBrains Mono + dot-matrix display,
  WCAG 2.2 AA.

## 13. Execution protocol

1. Read `README.md` + `CLAUDE.md`; write `docs/architecture/overview.md` and the initial ADRs
   (Postgres switch, DDD module layout, event strategy).
2. Switch the runtime DB to **PostgreSQL**; migrate the existing schema; keep migrations
   DB-neutral; get the suite green on Postgres in Docker.
3. Stand up the **design system** (tokens, fonts, theme toggle, base shadcn components) and a
   gallery route.
4. Build features **1→9 as vertical slices**, each finishing with green E2E + docs + ADRs.
5. After each slice, **recursively run the full suite** and fix regressions.
6. When all features are done and the full suite is green inside Docker, write a final
   `docs/architecture/overview.md` pass and a short demo script.

Begin now. Work autonomously, commit on feature branches with clear messages, and keep the docs
and tests moving in lockstep with the code.
