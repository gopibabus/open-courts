<?php

declare(strict_types=1);

namespace App\Domains\Booking\Events;

use App\Domains\Booking\Models\Booking;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A booking was cancelled (by its owner, or overridden by a member with
 * booking.manage), freeing the court window for re-booking. Dispatched AFTER the
 * writing transaction commits (ShouldDispatchAfterCommit).
 */
final class BookingCancelled implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Booking $booking,
    ) {}
}
