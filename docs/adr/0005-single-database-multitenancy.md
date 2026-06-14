# ADR-0005: Single-database, row-level multi-tenancy with subdomains

**Status:** accepted · **Date:** 2026-06-13

## Context

A tenant is a tennis club. We need isolation between clubs without the operational weight of a
database per tenant (per-tenant migrations, backups, connection management).

## Decision

- **Single database, row-level isolation.** Tenant-owned tables carry a `tenant_id` (string/UUID)
  column; the `Stancl\Tenancy\Database\Concerns\BelongsToTenant` trait adds a global scope and
  auto-fills `tenant_id`. The `DatabaseTenancyBootstrapper` and per-tenant DB jobs are disabled.
- **Subdomain identification** via `InitializeTenancyBySubdomain`. Central routes are constrained
  to `config('tenancy.central_domain')`; tenant routes to `{tenant}.<central>`.
- **Authorization, two axes:**
  - Per-club roles via spatie teams, with `team_foreign_key = tenant_id`; the active team is
    synced to the current tenant in `TenancyServiceProvider`.
  - Platform super-admin via `users.is_platform_admin` + a `Gate::before` hook (kept out of
    `$fillable`). Not a spatie role, because spatie's pivot `tenant_id` is non-null and part of
    the primary key, so a global null-team role can't be assigned.

## Consequences

- Cheap to operate; trivial cross-tenant reporting for the platform operator.
- **Tenant isolation is an application-enforced invariant** — every tenant-owned model must use
  `BelongsToTenant`, and we add tests proving no cross-tenant leakage.
- The `team_foreign_key` column on spatie tables is `string` to match UUID tenant ids.
