<?php

declare(strict_types=1);

namespace App\Domains\Facilities\Data;

/**
 * One recurring weekly window for a court: open on `dayOfWeek` (0=Mon..6=Sun)
 * from `opensAt` to `closesAt` (both "HH:MM" 24h strings).
 */
final readonly class AvailabilityWindowData
{
    public function __construct(
        public int $dayOfWeek,
        public string $opensAt,
        public string $closesAt,
    ) {}
}
