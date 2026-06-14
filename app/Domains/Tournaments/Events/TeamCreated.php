<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Events;

use App\Domains\Tournaments\Models\Team;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A team (squad) was created in a club. Dispatched AFTER the writing transaction commits
 * (ShouldDispatchAfterCommit) so listeners never act on a rollback. No listeners are
 * registered in this slice.
 */
final class TeamCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Team $team,
    ) {}
}
