<?php

declare(strict_types=1);

namespace App\Domains\Booking\Listeners;

use App\Domains\Booking\Events\BookingConfirmed;
use App\Domains\Notifications\Mail\BookingConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Queued, idempotent side effect of BookingConfirmed: email the booker a confirmation.
 * Auto-wired to the event by DomainEventServiceProvider (any handle(BookingConfirmed)
 * in a Listeners directory is discovered — no central registration).
 */
final class SendBookingConfirmationEmail implements ShouldQueue
{
    public function handle(BookingConfirmed $event): void
    {
        $booking = $event->booking->loadMissing(['court', 'user']);

        $recipient = $booking->user?->email;

        if ($recipient === null) {
            return;
        }

        Mail::to($recipient)->send(new BookingConfirmationMail($booking));
    }
}
