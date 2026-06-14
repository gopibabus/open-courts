<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Data;

/**
 * Input for registering an entrant into a category. `userId` is the entrant (usually the
 * current member); `partnerId` is the doubles/mixed partner (null for singles).
 */
final readonly class RegisterEntrantData
{
    public function __construct(
        public int $userId,
        public ?int $partnerId = null,
    ) {}
}
