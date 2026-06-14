# ADR-0003: Event-driven, but not event-sourced

**Status:** accepted · **Date:** 2026-06-13

## Context

We want an event architecture: meaningful state changes should be observable and trigger side
effects (notifications, projections, recalculations) without bloating controllers. Full
**event sourcing** (events as the source of truth, rebuilt aggregates) is powerful but costly
in complexity, tooling, and onboarding.

## Decision

- **Current-state persistence** (normal Eloquent tables) remains the source of truth.
- Model significant transitions as **domain events** (`BookingConfirmed`, `MatchScored`, …) —
  see the [event catalog](../events/event-catalog.md).
- **Side effects run in queued, idempotent listeners.** Events are dispatched **after** the
  writing transaction commits (transactional outbox / `afterCommit`), so a rolled-back write
  never emits an event.
- Build **read-model projections** (CQRS-lite) where they earn their keep: court calendars,
  tournament brackets, standings. Elsewhere, query the normalized tables directly.

We deliberately **do not** event-source aggregates.

## Consequences

- Simpler mental model and tooling than ES; we still get decoupled side effects and fast reads.
- Listeners must be idempotent (jobs can retry) and ordering-tolerant.
- If a context later genuinely needs full auditability/replay, it can adopt ES in isolation,
  recorded in a new ADR.
