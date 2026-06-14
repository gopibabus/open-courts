<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Data;

use App\Domains\Tournaments\Enums\TournamentFormat;

/**
 * Input for creating a tournament. `startsOn` / `endsOn` are ISO date strings (Y-m-d)
 * or null; Eloquent's date cast parses them on the model.
 */
final readonly class CreateTournamentData
{
    public function __construct(
        public string $name,
        public TournamentFormat $format,
        public ?string $startsOn,
        public ?string $endsOn,
    ) {}
}
