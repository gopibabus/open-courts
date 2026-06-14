# OpenTennis

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
| Dev/prod runtime | Docker (Apache + PHP-FPM-less mod_php, MySQL 8.4), supervisord (Apache + Laravel scheduler) |

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
| <http://lvh.me:8080> | the app (central domain) |
| <http://lvh.me:8080/register-club> | onboard a club |
| `http://<slug>.lvh.me:8080` | a club workspace (e.g. after onboarding) |
| <http://lvh.me:8080/ui> | the design-system gallery |
| <http://localhost:8025> | **Mailpit** — sent emails land here |

`*.lvh.me` resolves to 127.0.0.1 with no hosts-file changes, and the session cookie is shared
across club subdomains. To auto-seed on first boot, set `SEED_ON_START=true` in `docker/.docker.env`.

End-to-end against the running container: `E2E_BASE_URL=http://lvh.me:8080 npx playwright test`.

## Running locally without Docker

The host is pre-wired for zero-config **SQLite**:

```bash
composer install
npm install && npm run build      # or: npm run dev
php artisan migrate --seed
composer run dev                  # serves app + queue + vite + logs
```

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
app/Models/            Tenant, User, Court, Booking, Tournament, Team
app/Providers/         TenancyServiceProvider (single-DB + spatie team sync), AppServiceProvider (Gate::before)
routes/web.php         central (domain-constrained) routes
routes/tenant.php      subdomain club routes
database/migrations/   tenancy tables, permission tables (string team_id), tennis domain stubs
database/seeders/      RolePermissionSeeder (role matrix), DemoSeeder
docker/                Dockerfile, vhost.conf, supervisord.conf, start.sh, php.ini
```
