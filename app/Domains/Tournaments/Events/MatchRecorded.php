<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Events;

use App\Domains\Tournaments\Models\TournamentMatch;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A match result was recorded. Dispatched AFTER the writing transaction commits
 * (ShouldDispatchAfterCommit). The seam for future side effects (notify the players,
 * recompute standings); no listener today.
 */
final class MatchRecorded implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly TournamentMatch $match,
    ) {}
}
