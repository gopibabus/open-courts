# ADR-0002: DDD bounded-context module layout

**Status:** accepted · **Date:** 2026-06-13

## Context

The app spans several cohesive concerns (identity, tenancy, booking, tournaments, billing).
A flat `app/Models` + `app/Http` layout blurs these boundaries as the app grows.

## Decision

Organise code by **bounded context** under `app/Domains/<Context>/`. Each context contains:

```
app/Domains/Booking/
  Models/          Eloquent aggregates/entities (e.g. Booking, Court)
  Actions/         use-case commands (e.g. ReserveCourt)
  Data/            DTOs / value objects
  Events/          domain events (e.g. BookingConfirmed)
  Listeners/       queued side effects + projections
  Policies/        authorization
  Exceptions/      domain errors (e.g. CourtAlreadyBooked)
```

Contexts: **Identity, Tenancy, Membership, Facilities, Booking, Tournaments, Billing,
Notifications.** HTTP controllers stay thin (`app/Http`) and delegate to Actions. The
ubiquitous language (Club = tenant, Member, Court, Booking, Tournament, Draw, Match) is used
consistently in code and docs.

## Consequences

- Clear ownership and testability per context; easier to reason about and to hold in context.
- Namespaces move from `App\Models\X` to `App\Domains\<Context>\Models\X`; references in config,
  seeders, and tests are updated accordingly.
- Cross-context communication prefers **domain events** over direct calls where it reduces
  coupling (see [ADR-0003](0003-events-not-event-sourcing.md)).
