<?php

declare(strict_types=1);

namespace App\Domains\Facilities\Data;

/**
 * Input for creating or updating a court.
 */
final readonly class CourtData
{
    public function __construct(
        public string $name,
        public ?string $surface,
        public bool $isActive,
    ) {}
}
