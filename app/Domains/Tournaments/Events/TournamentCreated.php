<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Events;

use App\Domains\Tournaments\Models\Tournament;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A tournament was created in a club. Dispatched AFTER the writing transaction commits
 * (ShouldDispatchAfterCommit) so listeners never act on a rollback. No listeners are
 * registered in this slice.
 */
final class TournamentCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Tournament $tournament,
    ) {}
}
