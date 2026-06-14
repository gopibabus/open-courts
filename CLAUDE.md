# CLAUDE.md

Guidance for working in this repo. Keep it current as the app grows.

## What this is

Multi-tenant SaaS for tennis-court booking + tournaments. **Tenant = a tennis club.**
Laravel 12 + Inertia/React (TS). Status: skeleton — domain models are intentionally thin.

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
- Local URLs: `http://localhost:8080` (central), `http://<club>.localhost:8080` (club).

## Code layout (DDD — see ADR-0002)

Code is organised by **bounded context** under `app/Domains/<Context>/` (Identity, Tenancy,
Membership, Facilities, Booking, Tournaments, Billing, Notifications). Each context owns its
`Models/`, `Actions/`, `Data/`, `Events/`, `Listeners/`, `Policies/`, `Exceptions/`.

- Models live under `app/Domains/<Context>/Models/` — e.g. `App\Domains\Tenancy\Models\Tenant`,
  `App\Domains\Identity\Models\User`, `App\Domains\Facilities\Models\Court`. There is **no**
  `app/Models/`. `User`'s factory link is via an explicit `newFactory()` + `UserFactory::$model`.
- Controllers stay thin in `app/Http`; business logic goes in a context **Action**.
- Role → permission matrix: `database/seeders/RolePermissionSeeder::roleMatrix()` (user owns this).
- Demo data: `database/seeders/DemoSeeder.php`.
- The domain `teams` table (tournament squads) is unrelated to spatie's "teams" feature.

## Commands

```bash
php artisan test                          # Pest suite (SQLite in-memory)
npx playwright test                       # browser E2E (auto-starts `artisan serve` on lvh.me:8000)
php artisan migrate:fresh --seed          # runtime DB = Postgres (start it: docker compose up -d tennis-postgres)
docker compose up --build                 # full stack (app=Apache, db=Postgres, redis, mailpit)
npm run build                             # required before Inertia HTTP tests render HTML
```

App runs at **http://lvh.me:8000** (host dev) / **:8080** (Docker); clubs at `<slug>.lvh.me`.

## Vertical-slice pattern (follow for every feature)

DTO → Action (`app/Domains/<Ctx>/Actions`) → domain Event (after-commit) → queued Listener →
thin Controller + FormRequest → shadcn UI page → **Pest feature test + Playwright E2E** → docs
(`docs/features/<name>.md` with a Mermaid flow) + event-catalog row. Register event→listener in
`app/Providers/DomainEventServiceProvider.php`. Reference slice: **club onboarding**.

## More gotchas (learned the hard way)

- **Local subdomains use `lvh.me`, not `localhost`.** `*.localhost` can't share the session
  cookie across subdomains; `*.lvh.me` resolves to 127.0.0.1 and shares cookies. Set
  `CENTRAL_DOMAIN` / `SESSION_DOMAIN` / `APP_URL` to lvh.me locally.
- **Tests pin to `localhost`** via `phpunit.xml` (`APP_URL=http://localhost`,
  `CENTRAL_DOMAIN=localhost`) so domain-constrained central routes resolve under relative-path
  requests. Keep those two in sync if you change central routing.
- **Cross-subdomain redirects must use `Inertia::location()`**, not `redirect()->away()` —
  Inertia XHR can't follow a cross-origin 302.
- **`tenancy.asset_helper_tenancy` is `false`** — otherwise `asset()` (and thus Vite JS/CSS) is
  rewritten per-tenant and the SPA renders blank on club subdomains.

## Gotchas

- A `DEPRECATED: Accessing static trait property ... BelongsToTenant::$tenantIdColumn` notice
  comes from stancl/tenancy 3.10 on PHP 8.4. Harmless; silenced when `APP_DEBUG=false`.
- Inertia feature tests need either a built Vite manifest or `$this->withoutVite()`.
- Host vs container DB: host uses SQLite (`.env`); the container overrides to MySQL via
  `docker-compose.yml`'s `environment:` (Laravel's immutable dotenv won't clobber real env vars).
- `team_player` pivot rows need `tenant_id` passed explicitly on `attach()` (attach bypasses
  model events, so `BelongsToTenant` can't auto-fill it).
