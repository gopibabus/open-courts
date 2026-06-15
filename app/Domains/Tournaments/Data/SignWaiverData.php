<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Data;

/**
 * Input for a player signing a tournament waiver. `signature` is their typed full name.
 */
final readonly class SignWaiverData
{
    public function __construct(
        public int $tournamentId,
        public int $userId,
        public string $signature,
    ) {}
}
