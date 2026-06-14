<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Events;

use App\Domains\Identity\Models\User;
use App\Domains\Tournaments\Models\Team;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A club member was added to a team's roster. Dispatched AFTER commit
 * (ShouldDispatchAfterCommit) so listeners never act on a rollback. No listeners are
 * registered in this slice.
 */
final class PlayerAddedToTeam implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Team $team,
        public readonly User $player,
    ) {}
}
