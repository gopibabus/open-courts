<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Events;

use App\Domains\Identity\Models\User;
use App\Domains\Tournaments\Models\Tournament;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A club member was removed from a tournament's management (EC). Dispatched AFTER commit.
 * No listeners are registered in this slice.
 */
final class ManagerRemovedFromTournament implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Tournament $tournament,
        public readonly User $manager,
    ) {}
}
