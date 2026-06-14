<?php

declare(strict_types=1);

namespace App\Domains\Booking\Events;

use App\Domains\Booking\Models\Booking;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A member requested a booking and it passed every conflict check. Dispatched AFTER
 * the writing transaction commits (ShouldDispatchAfterCommit) so listeners never act
 * on a rolled-back reservation. In the current single-step flow this fires alongside
 * BookingConfirmed; it exists as the natural seam for a future "pending → confirmed"
 * (e.g. payment) step.
 */
final class BookingRequested implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Booking $booking,
    ) {}
}
