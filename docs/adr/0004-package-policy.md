# ADR-0004: Package policy — Laravel-first

**Status:** accepted · **Date:** 2026-06-13

## Context

The brief asks for "core Laravel first-hand packages" *and* for capabilities Laravel has no
first-party answer to (multi-tenancy, RBAC). These need reconciling.

## Decision

Choose dependencies in this strict order:

1. **Official Laravel packages first** — Fortify, Sanctum, Cashier (Stripe), Horizon, Reverb,
   Scout, Pennant, Telescope, Pint, Pest.
2. **Reputable, actively-maintained community package** *only where Laravel has no first-party
   option*:
   - **`stancl/tenancy`** — multi-tenancy.
   - **`spatie/laravel-permission`** — role/permission management.
3. **Build it ourselves** only as a last resort, justified in an ADR.

Never reinvent what (1) or (2) already covers well.

## Consequences

- Minimal, well-supported dependency surface; upgrades track the Laravel release cadence.
- The two community packages are load-bearing; their major-version upgrades get their own
  verification pass.
