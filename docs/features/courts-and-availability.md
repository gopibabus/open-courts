# Feature: Courts & availability

Club staff manage the club's **courts**, each court's recurring **weekly availability windows**,
and one-off **blackout** dates. Any member can view the courts; only staff with `court.manage`
can change them.

## Plain-English flow

1. A signed-in member visits **`/courts`** on their club subdomain (`<slug>.<central>`). They see
   every court in the club with its surface, active flag, weekly hours, and any blackouts.
2. A staff member with **`court.manage`** can:
   - **Add / edit / delete a court** (name, surface, active flag).
   - **Set a court's weekly availability** — a list of windows, each "on day X, open from
     `opens_at` to `closes_at`". Saving **replaces** the court's whole schedule (delete-then-insert).
   - **Add a blackout** — a one-off `starts_at → ends_at` period for a single court, or (when no
     court is chosen) for the **whole club**. Blackouts can be removed individually.
3. Members without `court.manage` get a read-only page; the mutating routes return **403**.

Everything is **tenant-scoped**: the `BelongsToTenant` trait stamps `tenant_id` on write and adds
a global scope on read, so a club only ever sees and edits its own courts.

## Sequence

```mermaid
sequenceDiagram
    actor S as Club staff (court.manage)
    participant R as Tenant route (can:court.manage)
    participant CC as CourtController / *Controller
    participant FR as FormRequest (validate)
    participant A as Action (use case)
    participant DB as Postgres
    participant E as Domain event (after commit)

    S->>R: POST /courts  (or PUT /courts/{c}/availability, POST /blackouts)
    R->>R: tenancy middleware sets team = club;<br/>can:court.manage gate
    R->>CC: dispatch
    CC->>FR: validate input
    CC->>A: handle(DTO / windows)
    activate A
    A->>DB: BEGIN
    A->>DB: persist (stamp tenant_id via BelongsToTenant)
    A->>DB: COMMIT
    A-->>E: CourtAdded / CourtAvailabilityChanged (after commit)
    deactivate A
    CC->>S: back() → Inertia reloads /courts
```

## Key invariants & decisions

- **Read is open, writes are gated.** `GET /courts` only needs `auth`. All mutating routes are
  wrapped in `->middleware('can:court.manage')` (the club-scoped spatie permission). A
  `CourtPolicy` mirrors this for `create`/`update`/`delete` and is attached to the model via
  `#[UsePolicy]` for completeness.
- **Availability is replace-all.** `SetCourtAvailability` deletes the court's existing windows and
  inserts the new set in one transaction, then emits `CourtAvailabilityChanged`. This keeps the
  schedule a single source of truth instead of diffing rows.
- **Blackouts can be club-wide.** `court_blackouts.court_id` is **nullable**; a null value blacks
  out every court. The store request validates a non-null `court_id` `exists` **within the current
  tenant** (the raw `exists` rule is constrained on `tenant_id` because it bypasses the global scope).
- **Day encoding.** `day_of_week` is a `smallInteger`, **0 = Monday … 6 = Sunday** (matches the
  UI's `['Mon'..'Sun']` order).
- **Events without listeners.** `CourtAdded` and `CourtAvailabilityChanged` are
  `ShouldDispatchAfterCommit`. No listeners are registered yet — they exist for downstream
  contexts (e.g. Booking recomputing bookable slots) to subscribe to later.
- **DB-neutral schema.** Only portable column types; `tenant_id` is a `string` FK to `tenants`
  (cascade), `court_id` is a `foreignId` constrained to `courts` (cascade).

## Where the code lives

| Concern | File |
| --- | --- |
| Court model (extended) | `app/Domains/Facilities/Models/Court.php` |
| Availability model | `app/Domains/Facilities/Models/CourtAvailability.php` |
| Blackout model | `app/Domains/Facilities/Models/CourtBlackout.php` |
| DTOs | `app/Domains/Facilities/Data/{CourtData,AvailabilityWindowData,BlackoutData}.php` |
| Use cases | `app/Domains/Facilities/Actions/{CreateCourt,UpdateCourt,SetCourtAvailability,AddCourtBlackout}.php` |
| Events | `app/Domains/Facilities/Events/{CourtAdded,CourtAvailabilityChanged}.php` |
| Policy | `app/Domains/Facilities/Policies/CourtPolicy.php` |
| HTTP controllers | `app/Http/Controllers/Facilities/{CourtController,CourtAvailabilityController,CourtBlackoutController}.php` |
| FormRequests | `app/Http/Requests/Facilities/{StoreCourtRequest,UpdateCourtRequest,SetCourtAvailabilityRequest,StoreCourtBlackoutRequest}.php` |
| Routes | `routes/tenant/facilities.php` |
| UI | `resources/js/pages/facilities/courts/index.tsx` |
| Migrations | `database/migrations/2026_06_14_010101_create_court_availability_table.php`, `…_010102_create_court_blackouts_table.php` |

## Routes

| Method | URI | Name | Guard |
| --- | --- | --- | --- |
| GET | `/courts` | `courts.index` | `auth` |
| POST | `/courts` | `courts.store` | `auth` + `can:court.manage` |
| PUT | `/courts/{court}` | `courts.update` | `auth` + `can:court.manage` |
| DELETE | `/courts/{court}` | `courts.destroy` | `auth` + `can:court.manage` |
| PUT | `/courts/{court}/availability` | `courts.availability.update` | `auth` + `can:court.manage` |
| POST | `/blackouts` | `blackouts.store` | `auth` + `can:court.manage` |
| DELETE | `/blackouts/{blackout}` | `blackouts.destroy` | `auth` + `can:court.manage` |

## Acceptance criteria (tested)

- ✅ A club-admin can create a court; `tenant_id` is stamped automatically.
- ✅ A court's weekly availability can be set (and replaces any prior windows).
- ✅ A blackout can be added for a court or for the whole club (null court).
- ✅ A member without `court.manage` is forbidden (403) from mutating; any member can view.
- ✅ Courts are isolated between clubs (a court in club A is invisible in club B).
- ✅ (E2E) Register a club via onboarding, open `/courts`, create a court, see it listed.

Tests: `tests/Feature/Facilities/CourtManagementTest.php` (Pest) · `tests/e2e/courts.spec.ts` (Playwright).
