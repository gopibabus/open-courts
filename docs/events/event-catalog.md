# Event Catalog

Domain events and their queued listeners. Events are dispatched **after commit** and listeners
are **idempotent** (see [ADR-0003](../adr/0003-events-not-event-sourcing.md)). This is a living
document — every feature slice adds its events here.

| Event | Context | Emitted when | Listeners (queued) |
| --- | --- | --- | --- |
| `ClubRegistered` | Tenancy | A club + owner are provisioned (after commit) | `SendClubWelcomeEmail` |

## Planned events by context

- **Tenancy / Onboarding:** `ClubRegistered`, `ClubSettingsUpdated`
- **Membership:** `MemberInvited`, `InvitationAccepted`, `RoleAssigned`
- **Facilities:** `CourtAdded`, `CourtDeactivated`, `AvailabilityChanged`
- **Booking:** `BookingRequested`, `BookingConfirmed`, `BookingCancelled`, `BookingCheckedIn`
- **Tournaments:** `TournamentCreated`, `RegistrationOpened`, `EntrantRegistered`,
  `DrawGenerated`, `MatchScheduled`, `MatchScored`, `MatchCompleted`, `TournamentCompleted`
- **Billing:** `SubscriptionStarted`, `SubscriptionCancelled`, `InvoicePaid`

## Conventions

- Event classes live in `app/Domains/<Context>/Events`.
- Listeners live in `app/Domains/<Context>/Listeners` and implement `ShouldQueue`.
- Each event records the `tenant_id` it occurred in (for tenant-scoped projections).
