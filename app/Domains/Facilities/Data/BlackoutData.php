<?php

declare(strict_types=1);

namespace App\Domains\Facilities\Data;

/**
 * Input for adding a one-off blackout. A null `courtId` blacks out the whole club.
 * `startsAt`/`endsAt` are datetime strings.
 */
final readonly class BlackoutData
{
    public function __construct(
        public ?int $courtId,
        public string $startsAt,
        public string $endsAt,
        public ?string $reason,
    ) {}
}
