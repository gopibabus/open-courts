<?php

declare(strict_types=1);

namespace App\Domains\Booking\Data;

/**
 * Input for booking a court. `startsAt`/`endsAt` are absolute datetimes (any
 * Carbon-parseable string, typically ISO-8601 from the browser). Optional pricing
 * is in integer minor units + an ISO currency code.
 */
final readonly class BookCourtData
{
    public function __construct(
        public int $courtId,
        public string $startsAt,
        public string $endsAt,
        public ?int $priceCents = null,
        public ?string $currency = null,
    ) {}
}
