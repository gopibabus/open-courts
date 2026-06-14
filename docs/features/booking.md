# Booking — conflict-free court reservations

A club member books a court for a time window. A booking is only created when the window is
**conflict-free**, which means all three of:

1. **Within open hours** — the window lies entirely inside one of the court's recurring weekly
   availability windows for that weekday.
2. **Not blacked out** — the window does not overlap any blackout, whether court-specific or a
   whole-club blackout.
3. **No overlap** — the window does not overlap an existing *reserved* booking on that court.

Members can cancel their own bookings; a member with `booking.manage` can cancel anyone's.

## The exact rules

- **Intervals are half-open `[starts_at, ends_at)`.** Two intervals overlap iff
  `a.start < b.end AND b.start < a.end`. Back-to-back bookings that merely touch at an endpoint
  (e.g. 10–11 and 11–12) do **not** conflict.
- **Availability containment.** `day_of_week` is `0=Mon..6=Sun`. The booking's weekday is taken
  from `starts_at`. The window passes only if some availability row for that weekday satisfies
  `opens_at <= start-time` **and** `end-time <= closes_at` (compared on the same calendar day).
  The whole window must fit inside a **single** availability row.
- **Blackouts.** Any blackout with `court_id = this court` **or** `court_id IS NULL` (whole-club)
  that overlaps the window rejects the booking.
- **Overlap.** Only bookings with `status = reserved` hold a court. Cancelled/completed bookings
  free the slot for re-booking.
- An **inactive court** (`is_active = false`) is never bookable.

## Concurrency strategy

Two members racing for the same slot must not both win. The whole check-then-create runs inside a
single `DB::transaction`, and the overlap query is `SELECT … FOR UPDATE` (`lockForUpdate()`):

- The first transaction to reach the overlap check locks the candidate rows for that court+window.
- A concurrent transaction blocks on that lock until the first commits, then re-reads and sees the
  freshly-created reserved row, so it is rejected with `CourtUnavailable::alreadyBooked()`.

This serialises conflicting bookers on the contended rows while leaving non-overlapping bookings on
the same court (or other courts) fully concurrent. Validated on PostgreSQL; the schema stays
DB-neutral (integer/time/dateTime/string only, per ADR-0001).

## Sequence — the conflict-check transaction

```mermaid
sequenceDiagram
    participant M as Member (browser)
    participant C as BookingController
    participant A as BookCourt (action)
    participant DB as Postgres
    participant Q as Queue (events)

    M->>C: POST /bookings {court_id, starts_at, ends_at}
    C->>C: StoreBookingRequest<br/>(court in tenant, ends_at > starts_at)
    C->>A: handle(userId, BookCourtData)
    rect rgb(34,34,34)
    note over A,DB: DB::transaction
    A->>DB: load court (tenant-scoped); reject if inactive
    A->>DB: availability rows for weekday → must contain window
    A->>DB: blackouts (court OR whole-club) overlapping window?
    A->>DB: SELECT reserved bookings overlapping window FOR UPDATE
    alt any check fails
        A-->>C: throw CourtUnavailable
        C-->>M: 422 validation error on "booking"
    else all pass
        A->>DB: INSERT booking (status=reserved)
    end
    end
    A-->>Q: BookingRequested + BookingConfirmed (after commit)
    Q-->>Q: SendBookingConfirmationEmail (queued)
    C-->>M: redirect back ("Booking confirmed.")
```

Cancellation is simpler: the controller authorizes (owner, or `booking.manage`), then
`CancelBooking` sets `status = cancelled` inside a transaction and emits `BookingCancelled`
after commit (idempotent — cancelling an already-cancelled booking is a no-op).

## Code locations

| Concern | File |
| --- | --- |
| Status enum | `app/Domains/Booking/Enums/BookingStatus.php` |
| Model (+ pricing, `reserved` scope) | `app/Domains/Booking/Models/Booking.php` |
| Pricing migration | `database/migrations/2026_06_14_040101_add_pricing_to_bookings_table.php` |
| Command DTO | `app/Domains/Booking/Data/BookCourtData.php` |
| Conflict-free core | `app/Domains/Booking/Actions/BookCourt.php` |
| Cancel | `app/Domains/Booking/Actions/CancelBooking.php` |
| Domain exception | `app/Domains/Booking/Exceptions/CourtUnavailable.php` |
| Events | `app/Domains/Booking/Events/{BookingRequested,BookingConfirmed,BookingCancelled}.php` |
| Confirmation listener | `app/Domains/Booking/Listeners/SendBookingConfirmationEmail.php` |
| Confirmation mail + view | `app/Domains/Notifications/Mail/BookingConfirmationMail.php`, `resources/views/mail/booking-confirmation.blade.php` |
| Controller | `app/Http/Controllers/Booking/BookingController.php` |
| FormRequest | `app/Http/Requests/Booking/StoreBookingRequest.php` |
| Routes | `routes/tenant/booking.php` |
| UI | `resources/js/pages/booking/index.tsx` |
| Feature test | `tests/Feature/Booking/BookingTest.php` |
| E2E | `tests/e2e/booking.spec.ts` |

## Routes (all on `<club>.<central_domain>`, all `auth`)

| Name | Method + URI | Guard |
| --- | --- | --- |
| `bookings.index` | `GET /bookings` | auth (any member) |
| `bookings.store` | `POST /bookings` | auth + `can:court.book` |
| `bookings.destroy` | `DELETE /bookings/{booking}` | auth; owner **or** `can:booking.manage` (in controller) |

## Events

| Event | When | Listener |
| --- | --- | --- |
| `BookingRequested` | booking passed all checks (after commit) | — (seam for a future payment/pending step) |
| `BookingConfirmed` | booking reserved (after commit) | `SendBookingConfirmationEmail` (queued) |
| `BookingCancelled` | booking cancelled (after commit) | — |

All implement `ShouldDispatchAfterCommit`.

## Database columns added (to `bookings`)

| Column | Type | Notes |
| --- | --- | --- |
| `price_cents` | `integer` nullable | money in minor units (never a float) |
| `currency` | `string(3)` nullable | ISO-4217 code |

The `(court_id, starts_at, ends_at)` index that backs the overlap query already exists from the
original `create_bookings_table` migration.

## Permissions

| Permission | Used for | Source |
| --- | --- | --- |
| `court.book` | create a booking | already seeded; `member` role already has it |
| `booking.manage` | cancel another member's booking | already seeded; granted to `club-admin` |

No new permissions are required.

## Acceptance criteria

- [x] A member with `court.book` can book an available slot (tenant-scoped).
- [x] An overlapping booking on the same court is rejected (422 / `CourtUnavailable`).
- [x] Back-to-back (endpoint-touching) bookings are allowed.
- [x] A booking outside the court's availability is rejected.
- [x] A booking during a court or whole-club blackout is rejected.
- [x] A member can cancel their own booking; cancelling frees the slot for re-booking.
- [x] A member cannot cancel another member's booking without `booking.manage`; a holder of
      `booking.manage` can.
- [x] Bookings are isolated between clubs.
- [x] `BookingConfirmed` drives a queued confirmation email.
```
