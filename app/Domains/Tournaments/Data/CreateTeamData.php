<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Data;

/**
 * Input for creating a team (squad). A team always belongs to a tournament, so
 * `tournamentId` is required — teams are specific to a tournament.
 */
final readonly class CreateTeamData
{
    public function __construct(
        public string $name,
        public int $tournamentId,
    ) {}
}
