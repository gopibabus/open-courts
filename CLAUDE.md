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
php artisan test                          # full suite (SQLite in-memory)
php artisan test --filter=MultiTenancyTest
php artisan migrate:fresh --seed          # host = SQLite
docker compose up --build                 # container = MySQL
npm run build                             # required before Inertia HTTP tests render HTML
```

## Gotchas

- A `DEPRECATED: Accessing static trait property ... BelongsToTenant::$tenantIdColumn` notice
  comes from stancl/tenancy 3.10 on PHP 8.4. Harmless; silenced when `APP_DEBUG=false`.
- Inertia feature tests need either a built Vite manifest or `$this->withoutVite()`.
- Host vs container DB: host uses SQLite (`.env`); the container overrides to MySQL via
  `docker-compose.yml`'s `environment:` (Laravel's immutable dotenv won't clobber real env vars).
- `team_player` pivot rows need `tenant_id` passed explicitly on `attach()` (attach bypasses
  model events, so `BelongsToTenant` can't auto-fill it).
