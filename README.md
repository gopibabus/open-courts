# Open Courts

A multi-tenant SaaS for **tennis-court booking and running tournaments**. Each tenant
is a tennis **club**; every club has its own courts, members, bookings, tournaments,
and teams, all isolated within a single shared database.

> Status: **project skeleton**. The tenancy + role foundation is in place and verified;
> the domain models (courts, bookings, tournaments, teams) are deliberately thin and
> will grow as the booking/tournament rules are defined.

## Stack

| Concern | Choice |
| --- | --- |
| Framework | Laravel 12 (PHP 8.4) |
| Frontend | Inertia + React 19 + TypeScript + Tailwind (official Laravel React starter kit) |
| Multi-tenancy | [`stancl/tenancy`](https://tenancyforlaravel.com) — single-database, subdomain identification |
| Roles & permissions | [`spatie/laravel-permission`](https://spatie.be/docs/laravel-permission) — teams mode, `team_id` = tenant id |
| Dev/prod runtime | Docker (Apache + mod_php), **PostgreSQL**, Redis, Mailpit; supervisord runs Apache + a queue worker |

## Architecture at a glance

- **One database, row-level isolation.** Tenant-owned tables carry a `tenant_id`
  column; the `BelongsToTenant` trait adds a global scope so queries are auto-filtered
  to the active club. No per-tenant databases are created (the `DatabaseTenancyBootstrapper`
  and the `CreateDatabase`/`MigrateDatabase` jobs are intentionally disabled).
- **Clubs live on subdomains.** `localhost` (the central domain) hosts the public site
  and platform area; `smashclub.localhost` is a club's workspace. The tenant is resolved
  from the host by `InitializeTenancyBySubdomain`.
- **Two axes of authority:**
  - *Club roles* (`club-admin`, `coach`, `member`, …) are scoped **per club** via spatie's
    teams feature — a Coach in one club isn't a Coach in another.
  - *Platform admin* is a separate `is_platform_admin` flag on `users` + a `Gate::before`
    hook; it bypasses all checks across every club.

```
localhost/                 → central: welcome, platform area
localhost/dashboard        → central dashboard (starter kit)
smashclub.localhost/       → club dashboard (tenant-scoped, see routes/tenant.php)
login, register, settings  → universal (work on central and club subdomains)
```

## Running with Docker (recommended)

```bash
cp .env.example .env          # has a working APP_KEY/config out of the box
docker compose up --build     # starts: app (Apache), postgres, redis, mailpit
docker compose exec tennis-web php artisan db:seed   # optional: demo platform-admin + club
```

`docker compose up` starts four services and runs migrations on boot:

| URL | What |
| --- | --- |
| <http://localhost:8080> | the app (central domain) |
| <http://localhost:8080/register-club> | onboard a club |
| `http://<slug>.localhost:8080` | a club workspace (e.g. after onboarding) |
| <http://localhost:8080/ui> | the design-system gallery |
| <http://localhost:8025> | **Mailpit** — sent emails land here |

`*.localhost` resolves to 127.0.0.1 with no hosts-file changes (macOS + modern browsers). The
container runs `SESSION_DOMAIN=null`, so the session cookie is **host-only** — log in directly on
the club subdomain (`<slug>.localhost:8080`), not on `localhost`. To auto-seed on first boot, set
`SEED_ON_START=true` in `docker/.docker.env`.

The Playwright E2E suite targets the host dev server (`lvh.me:8000`, below) — its specs assume
`*.lvh.me` — so the Docker container is for manual browsing rather than `npx playwright test`.

## Running locally without Docker

The host runs against the same **PostgreSQL** as Docker — start just the database, then the app
(served at <http://lvh.me:8000>, clubs at `http://<slug>.lvh.me:8000`):

```bash
composer install
npm install && npm run build         # or: npm run dev
docker compose up -d tennis-postgres # the database
php artisan migrate --seed
composer run dev                     # serves app + queue + vite + logs
```

> The **test** suite is separate: it always runs on in-memory SQLite (forced by `phpunit.xml`
> + `tests/bootstrap.php`), so `php artisan test` never touches your Postgres data.

## Demo credentials (after seeding)

| Who | Email | Password | Notes |
| --- | --- | --- | --- |
| Platform admin | `admin@opentennis.test` | `password` | bypasses all checks (`is_platform_admin`) |
| Club admin | `owner@smashclub.test` | `password` | `club-admin` in **Smash Tennis Club** |

## The role matrix is yours to define

`database/seeders/RolePermissionSeeder.php` → `roleMatrix()` is where club roles map to
permissions. The starter values (`club-admin`, `coach`, `member`) are a guess — edit them
to match how clubs actually operate. See the `TODO` block in that method.

## Testing

```bash
php artisan test
```

`tests/Feature/MultiTenancyTest.php` locks in the core invariants: court scoping per club,
per-club roles, the platform-admin gate bypass, and subdomain resolution.

## Project layout (skeleton pieces)

```
app/Domains/<Ctx>/     code by bounded context — Identity, Tenancy, Membership, Facilities,
                       Booking, Tournaments, Support, Notifications (Models/Actions/Data/Events/…)
app/Providers/         TenancyServiceProvider (single-DB + spatie team sync), AppServiceProvider (Gate::before)
routes/web.php         central (domain-constrained) routes + universal auth/settings
routes/tenant.php      subdomain club routes (auto-loads routes/tenant/*.php, one file per context)
database/migrations/   tenancy tables, permission tables (string team_id), tennis domain tables
database/seeders/      RolePermissionSeeder (role matrix), DemoSeeder
docker/                Dockerfile, vhost.conf, supervisord.conf, start.sh, php.ini
```
