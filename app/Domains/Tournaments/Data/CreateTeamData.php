<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Data;

/**
 * Input for creating a team (squad) in the current club. `tournamentId` is optional —
 * a team can exist before being entered into a tournament draw (a later slice).
 */
final readonly class CreateTeamData
{
    public function __construct(
        public string $name,
        public ?int $tournamentId = null,
    ) {}
}
