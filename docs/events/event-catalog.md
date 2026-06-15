# Event Catalog

Domain events and their queued listeners. Events are dispatched **after commit** and listeners
are **idempotent** (see [ADR-0003](../adr/0003-events-not-event-sourcing.md)). This is a living
document — every feature slice adds its events here.

| Event | Context | Emitted when | Listeners (queued) |
| --- | --- | --- | --- |
| `ClubRegistered` | Tenancy | A club + owner are provisioned (after commit) | `SendClubWelcomeEmail` |
| `MemberInvited` | Membership | A member is invited by email | `SendInvitationEmail` |
| `InvitationAccepted` | Membership | An invitee joins the club | — |
| `RoleAssigned` | Membership | A member's club role changes | — |
| `CourtAdded` | Facilities | A court is created | — |
| `CourtAvailabilityChanged` | Facilities | A court's weekly windows are replaced | — |
| `TournamentCreated` | Tournaments | A tournament is created | — |
| `RegistrationOpened` | Tournaments | A tournament opens registration | — |
| `EntrantRegistered` | Tournaments | An entrant registers for a category | — |
| `TeamCreated` | Tournaments | A team (squad) is created | — |
| `PlayerAddedToTeam` | Tournaments | A member is added to a team roster | — |
| `PlayerRemovedFromTeam` | Tournaments | A member is removed from a roster | — |
| `MatchRecorded` | Tournaments | A match result is recorded (after commit) | — |
| `WaiverSigned` | Tournaments | A player signs a tournament waiver (after commit) | — |
| `WaiverTemplateUpdated` | Tournaments | A club's waiver template is created or edited (after commit) | — |
| `ManagerAddedToTournament` | Tournaments | A member is added to a tournament's management (EC) | — |
| `ManagerRemovedFromTournament` | Tournaments | A member is removed from a tournament's management | — |
| `BookingRequested` | Booking | A booking is requested | — |
| `BookingConfirmed` | Booking | A court booking is confirmed | `SendBookingConfirmationEmail` |
| `BookingCancelled` | Booking | A booking is cancelled | — |
| `SupportRequestSubmitted` | Support | A member files a help-desk request (after commit) | `SendSupportRequestNotification` |
| `ClubSuspended` | Tenancy | A platform operator suspends a club | — |
| `ClubReactivated` | Tenancy | A suspended club is reactivated | — |

> All events implement `ShouldDispatchAfterCommit`; listeners are auto-discovered from
> `app/Domains/*/Listeners` (see `App\Providers\DomainEventServiceProvider`). Events without a
> listener today are emitted for future projections/notifications.

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
