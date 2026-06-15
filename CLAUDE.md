# CLAUDE.md

Guidance for working in this repo. Keep it current as the app grows.

## What this is

Multi-tenant SaaS for tennis-court booking + tournaments, branded **Open Courts**.
**Tenant = a tennis club.** Laravel 12 + Inertia/React (TS). Status: skeleton — domain
models are intentionally thin.

App identity (name, tagline, logos, favicon, support email) is centralized in
**`config/branding.php`** and shared to the frontend as the Inertia `branding` prop (see the
`Logo` component + `HandleInertiaRequests`). Change branding there, not inline — there is no
Laravel logo anywhere (`app-logo-icon` now renders the branded `<Logo>`).

## Non-negotiable conventions

- **Don't reinvent the wheel.** Prefer Laravel core, then a well-maintained community
  package, and only build from scratch as a last resort.
- **Single-database, row-level tenancy** (`stancl/tenancy`). Tenant-owned models use the
  `Stancl\Tenancy\Database\Concerns\BelongsToTenant` trait and a `tenant_id` (string) column.
  Never add the `DatabaseTenancyBootstrapper` back — there are no per-tenant databases.
- **Tenant id is a string (UUID).** Anything joining to it — including spatie's team key —
  must be a `string` column, not `unsignedBigInteger`. (See the edited permission migration.)
- **Roles are club-scoped** via spatie teams, where `team_foreign_key = tenant_id`. The active
  team is synced to the current tenant in `TenancyServiceProvider::syncPermissionTeamWithTenant()`.
  To assign/check a role for a specific club, set context first:
  `app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getTenantKey())`.
- **Platform super-admin is NOT a spatie role.** It's the `users.is_platform_admin` flag +
  a `Gate::before` in `AppServiceProvider`. (spatie's `model_has_roles.tenant_id` is NOT NULL
  and part of the PK, so a null-team "global role" can't be assigned — hence the flag.)
  Keep `is_platform_admin` OUT of `$fillable`.

## Routing (subdomains)

- Central routes (`routes/web.php`) are constrained to `config('tenancy.central_domain')`.
- Club routes (`routes/tenant.php`) use `Route::domain('{tenant}.'.config('tenancy.central_domain'))`
  + `InitializeTenancyBySubdomain` + `PreventAccessFromCentralDomains`, with tenancy middleware
  **before** `auth`. Both `/` routes are domain-constrained so they don't shadow each other
  (Laravel keys routes by domain+URI).
- **Universal routes** (`routes/settings.php`, `routes/auth.php`) sit OUTSIDE the central-domain
  group, so they serve on both central and club subdomains. Settings additionally runs
  `App\Http\Middleware\InitializeTenancyForUniversalRoutes`, which initializes tenancy on a
  club subdomain (graceful no-op centrally) so `/settings/*` carries the `club` prop and renders
  inside the club shell.
- Local URLs (Docker): `http://localhost:8080` (central), `http://<slug>.localhost:8080` (club).
  Host dev / E2E use `lvh.me` instead — see the serving note under "Gotchas".

## Code layout (DDD — see ADR-0002)

Code is organised by **bounded context** under `app/Domains/<Context>/` (Identity, Tenancy,
Membership, Facilities, Booking, Tournaments, Support, Billing, Notifications). Each context
owns its `Models/`, `Actions/`, `Data/`, `Events/`, `Listeners/`, `Policies/`, `Exceptions/`.

- Models live under `app/Domains/<Context>/Models/` — e.g. `App\Domains\Tenancy\Models\Tenant`,
  `App\Domains\Identity\Models\User`, `App\Domains\Facilities\Models\Court`. There is **no**
  `app/Models/`. `User`'s factory link is via an explicit `newFactory()` + `UserFactory::$model`.
- Controllers stay thin in `app/Http`; business logic goes in a context **Action**.
- Role → permission matrix: `database/seeders/RolePermissionSeeder::roleMatrix()` (user owns this).
- Demo data: `database/seeders/DemoSeeder.php`.
- The domain `teams` table (tournament squads) is unrelated to spatie's "teams" feature.
- **Teams are tournament-scoped — they never exist standalone.** A team belongs to a
  tournament (created via `tournaments.teams.store`); its roster page is reached from the
  tournament (there is **no** `teams.index`). A member may be on **one team per tournament**
  (DB unique on `team_player(tournament_id, user_id)` + an `AddPlayerToTeam` guard + the roster
  picker filter). Each tournament also has its own **management / EC** (`tournament_management`).

## Frontend (Inertia shell + shared props)

- **Club shell:** every authenticated club page renders inside `ClubLayout` (collapsible
  sidebar + sticky topbar) and is **full-width** (no `mx-auto max-w-*`) so it reads end-to-end
  like the dashboard. The dashboard is a read-only aggregation; create/manage actions live in
  the sidebar nav + per-page dialogs, not on the dashboard.
- **Shared Inertia props** (`HandleInertiaRequests`): `auth.user` (with `is_platform_admin`
  pinned to a real bool), `auth.clubs` (every club the user belongs to — powers the sidebar
  **club switcher**), `club` (the active tenant, or null centrally), and `branding`
  (`config('branding')`). The shell reads these; controllers don't re-pass the club.
- **A member can belong to multiple clubs** — `tenant_user` has a composite unique
  (`tenant_id`, `user_id`), not a unique `user_id`. The sidebar footer becomes a switcher when
  `auth.clubs.length > 1`.
- **Account settings render in the club shell on a subdomain** (see Routing): `SettingsPageLayout`
  wraps `/settings/*` in `ClubLayout` when the `club` prop is present, else `AppLayout`.
- **Help / Support slice:** `/help` (in the club shell) → `SupportRequest` (tenant-scoped) →
  `SubmitSupportRequest` → `SupportRequestSubmitted` (after commit) → queued
  `SendSupportRequestNotification` mails `config('branding.support_email')`.

## Commands

```bash
php artisan test                          # Pest suite (SQLite in-memory)
npx playwright test                       # browser E2E (auto-starts `artisan serve` on lvh.me:8000)
php artisan migrate:fresh --seed          # runtime DB = Postgres (start it: docker compose up -d tennis-postgres)
docker compose up --build                 # full stack (app=Apache, db=Postgres, redis, mailpit)
npm run build                             # required before Inertia HTTP tests render HTML
```

Local URLs — **Docker (default): `http://localhost:8080`** (clubs `http://<slug>.localhost:8080`);
**host dev: `http://lvh.me:8000`** (clubs `http://<slug>.lvh.me:8000`). Playwright drives host
`artisan serve` on `lvh.me:8000` (its specs assume `*.lvh.me`); the Docker container is for manual
browsing on `localhost:8080`. See the serving note under "Gotchas".

## Docker (must stay true)

- The `app` image needs **`pdo_pgsql` + `redis` (phpredis)** PHP extensions (in `docker/Dockerfile`)
  and a **queue worker** in `docker/supervisord.conf` — without the worker, `ShouldQueue`
  listeners (welcome/invitation/booking emails) never run and no mail is sent.
- Container env (in `docker-compose.yml`) puts cache/session/queue on **redis** and mail on
  **mailpit** (SMTP `tennis-mailpit:1025`, web UI :8025). Redis cache keys live in **DB 1**
  (`REDIS_CACHE_DB`), so `redis-cli -n 1 keys '*'`.
- After changing the Dockerfile/start.sh/supervisord, **rebuild** (`docker compose build`) —
  `/usr/local/bin/start` and the extensions are baked into the image, not bind-mounted.
- E2E specs must be **port-agnostic**: derive the club origin from `new URL(page.url()).origin`
  (never hardcode `:8000`), and **wait for a save's PUT/redirect** before navigating away — the
  Apache app has higher latency than `artisan serve` and will race otherwise.

## Vertical-slice pattern (follow for every feature)

DTO → Action (`app/Domains/<Ctx>/Actions`) → domain Event (after-commit) → queued Listener →
thin Controller + FormRequest → shadcn UI page → **Pest feature test + Playwright E2E** → docs
(`docs/features/<name>.md` with a Mermaid flow) + event-catalog row. Register event→listener in
`app/Providers/DomainEventServiceProvider.php`. Reference slice: **club onboarding**.

## More gotchas (learned the hard way)

- **Two local-serving setups — pick by cookie needs:**
  - **Docker (default) → `localhost`.** `docker-compose.yml` sets `CENTRAL_DOMAIN=localhost`,
    `APP_URL=http://localhost:8080`, `SESSION_DOMAIN=null`. `*.localhost` resolves to 127.0.0.1
    (macOS), so clubs live at `http://<slug>.localhost:8080`. Because `SESSION_DOMAIN=null` the
    session cookie is **host-only** — so **log in directly on the club subdomain**; a login on
    `localhost` is NOT shared to `<slug>.localhost`.
  - **Host dev / E2E → `lvh.me`.** The host `.env` sets `CENTRAL_DOMAIN=SESSION_DOMAIN=lvh.me`
    and `APP_URL=http://lvh.me:8000`; `*.lvh.me` resolves to 127.0.0.1 and `SESSION_DOMAIN=lvh.me`
    **shares** the cookie across subdomains (one login works everywhere). Playwright uses this.
  - Either domain works for subdomain identification because `config('tenancy.central_domains')`
    lists `localhost`, `127.0.0.1` **and** `env('CENTRAL_DOMAIN')`.
- **Tests pin to `localhost`** via `phpunit.xml` (`APP_URL=http://localhost`,
  `CENTRAL_DOMAIN=localhost`) so domain-constrained central routes resolve under relative-path
  requests. Keep those two in sync if you change central routing.
- **Cross-subdomain redirects must use `Inertia::location()`**, not `redirect()->away()` —
  Inertia XHR can't follow a cross-origin 302.
- **`tenancy.asset_helper_tenancy` is `false`** — otherwise `asset()` (and thus Vite JS/CSS) is
  rewritten per-tenant and the SPA renders blank on club subdomains.
- **The `{tenant}` domain route param is forgotten at request time** (`ForgetTenantRouteParameter`
  middleware) and supplied as a `URL::default` for generation. Without forgetting it, Laravel
  passes it as a leading positional arg, so `Foo $foo` route-model binding receives the tenant
  slug string and `route('x.show', $model)` feeds the model into `{tenant}`. This is already
  wired for all `routes/tenant/*.php` — just write normal controllers + `route()` calls.
- **Don't name a test property `$seeder`** — it collides with `RefreshDatabase::$seeder` and
  throws "accessed before initialization" in `setUp()`. Use another name.
- **`Event::fake()` (no args) before creating roles/permissions breaks spatie** — it swallows the
  model events that bust spatie's permission cache, causing `PermissionDoesNotExist`. Provision
  roles BEFORE faking, or fake only specific event classes (`Event::fake([X::class])`).
- **NEVER run `php artisan test` inside the running app container without forced test env.**
  The container exports real `DB_CONNECTION=pgsql` (+ redis/mailpit) via `docker-compose.yml`;
  a plain `<env>` in `phpunit.xml` is IGNORED when the var already exists in the real environment,
  so the suite would run against the **live Postgres DB** and `RefreshDatabase`'s `migrate:fresh`
  **drops every table** (this wiped all tenants once). Fixed by `tests/bootstrap.php`, which
  overwrites `$_SERVER`/`$_ENV`/`putenv` BEFORE the framework boots (Laravel's immutable dotenv
  then keeps the test values), plus `force="true"` on every `phpunit.xml` `<env>`. The guardrail
  `TestSuiteUsesIsolatedDatabaseTest` asserts the active connection is in-memory sqlite — if it
  fails, STOP, do not run the rest of the suite.

## Gotchas

- A `DEPRECATED: Accessing static trait property ... BelongsToTenant::$tenantIdColumn` notice
  comes from stancl/tenancy 3.10 on PHP 8.4. Harmless; silenced when `APP_DEBUG=false`.
- Inertia feature tests need either a built Vite manifest or `$this->withoutVite()`.
- Host vs container DB: both run **Postgres** — the host via `.env` (`DB_CONNECTION=pgsql`, db
  `open_tennis`, start it with `docker compose up -d tennis-postgres`), the container via
  `docker-compose.yml`'s `environment:` pointing at the `tennis-postgres` service (Laravel's
  immutable dotenv won't clobber those real env vars). The **test** suite ignores both and runs
  on in-memory SQLite, forced by `phpunit.xml` + `tests/bootstrap.php`.
- `team_player` pivot rows need `tenant_id` passed explicitly on `attach()` (attach bypasses
  model events, so `BelongsToTenant` can't auto-fill it).
