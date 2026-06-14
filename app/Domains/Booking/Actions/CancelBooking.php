<?php

declare(strict_types=1);

namespace App\Domains\Booking\Actions;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Events\BookingCancelled;
use App\Domains\Booking\Models\Booking;
use Illuminate\Support\Facades\DB;

/**
 * Cancel a booking, freeing its court window for re-booking. Authorization (owner, or
 * a member with booking.manage) is enforced by the controller — this action is the
 * pure state transition. Idempotent: cancelling an already-cancelled booking is a
 * no-op and does NOT re-emit the event.
 *
 * BookingCancelled fires after commit.
 */
final class CancelBooking
{
    public function handle(Booking $booking): Booking
    {
        if ($booking->status === BookingStatus::Cancelled) {
            return $booking;
        }

        return DB::transaction(function () use ($booking): Booking {
            $booking->update(['status' => BookingStatus::Cancelled]);

            BookingCancelled::dispatch($booking);

            return $booking;
        });
    }
}
