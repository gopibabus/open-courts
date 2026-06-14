<?php

declare(strict_types=1);

namespace App\Domains\Booking\Events;

use App\Domains\Booking\Models\Booking;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A booking is confirmed and now holds the court for its window. Dispatched AFTER the
 * writing transaction commits (ShouldDispatchAfterCommit) so listeners (e.g. the
 * confirmation email) never act on a rolled-back reservation.
 */
final class BookingConfirmed implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Booking $booking,
    ) {}
}
