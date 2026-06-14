<?php

declare(strict_types=1);

namespace App\Domains\Booking\Enums;

/**
 * Lifecycle of a court booking. String-backed PHP enum stored in the
 * `bookings.status` column (DB-neutral — no DB enum, per ADR-0001).
 *
 *   - Reserved:  an active reservation that holds the court for its window. Only
 *                reserved bookings participate in overlap/conflict checks.
 *   - Cancelled: withdrawn by the member (or an override by booking.manage). Frees
 *                the window for re-booking.
 *   - Completed: the window has elapsed (terminal, archival).
 */
enum BookingStatus: string
{
    case Reserved = 'reserved';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
}
