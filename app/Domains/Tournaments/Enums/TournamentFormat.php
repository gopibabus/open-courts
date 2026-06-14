<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Enums;

/**
 * How a tournament is run. String-backed PHP enum stored in the `tournaments.format`
 * column (DB-neutral — no DB enum, per ADR-0001).
 *
 * NOTE: this slice only records the chosen format. Actually generating the bracket /
 * round-robin schedule (the DRAW) is OUT OF SCOPE here — see docs/features/tournaments.md.
 */
enum TournamentFormat: string
{
    case SingleElimination = 'single_elimination';
    case RoundRobin = 'round_robin';
}
