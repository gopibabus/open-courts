# ADR-0001: PostgreSQL at runtime, DB-neutral schema

**Status:** accepted · **Date:** 2026-06-13

## Context

The runtime database is PostgreSQL 17. However, the whole data layer goes through Eloquent,
and we want to keep the option of running tests on SQLite (fast, in-memory) and to avoid
locking the schema to one vendor.

## Decision

- Use **only the portable Eloquent Schema-builder column types**: `uuid`, `string`, `text`,
  `integer`, `bigInteger`, `decimal`, `boolean`, `date`, `dateTime`/`timestamp`, `json`.
- **No vendor-specific DDL** in migrations — no Postgres native `enum`, array, `hstore`, or
  `jsonb`-only features. Enums live in PHP (native enum + Eloquent cast) over a `string` column.
- **UUID (string) primary keys** everywhere — DB-neutral, non-guessable, multi-tenant friendly.
- **Money** as integer minor units + currency code; **timestamps** stored UTC.
- The test suite runs on **in-memory SQLite** (`phpunit.xml`); the app runs on Postgres in
  Docker. Both must stay green — this is the portability guard.

## Consequences

- We trade some Postgres power (native enums, partial indexes, JSONB operators) for portability
  and test speed. If we ever need a Postgres-only feature, it goes through a raw expression
  guarded by the connection driver and is recorded in a new ADR.
- Concurrency guards (e.g. no double-booking) are expressed portably (transactions + row locks +
  unique constraints), not via Postgres exclusion constraints.
