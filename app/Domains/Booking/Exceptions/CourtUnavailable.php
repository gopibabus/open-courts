<?php

declare(strict_types=1);

namespace App\Domains\Booking\Exceptions;

use RuntimeException;

/**
 * Thrown when a court cannot be booked for the requested window: the window falls
 * outside the court's weekly availability, lands in a blackout, or overlaps an
 * existing reserved booking on that court.
 *
 * Controllers translate this into a 422 validation error (see BookingController).
 */
final class CourtUnavailable extends RuntimeException
{
    public static function inactiveCourt(): self
    {
        return new self('This court is not currently bookable.');
    }

    public static function outsideAvailability(): self
    {
        return new self('That time is outside the court\'s open hours.');
    }

    public static function duringBlackout(): self
    {
        return new self('The court is closed (blackout) during that time.');
    }

    public static function alreadyBooked(): self
    {
        return new self('That slot overlaps an existing booking on this court.');
    }
}
