<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Events;

use App\Domains\Identity\Models\User;
use App\Domains\Tournaments\Models\Tournament;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A club member was added to a tournament's management (EC). Dispatched AFTER commit so
 * listeners never act on a rollback. No listeners are registered in this slice.
 */
final class ManagerAddedToTournament implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Tournament $tournament,
        public readonly User $manager,
    ) {}
}
