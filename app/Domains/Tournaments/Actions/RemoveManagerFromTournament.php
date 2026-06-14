<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Tournaments\Events\ManagerRemovedFromTournament;
use App\Domains\Tournaments\Models\Tournament;
use Illuminate\Support\Facades\DB;

/**
 * Remove a club member from a tournament's management (EC). Idempotent — detaching
 * someone who is not on the management is a no-op. The ManagerRemovedFromTournament
 * event fires after commit.
 */
final class RemoveManagerFromTournament
{
    public function handle(Tournament $tournament, User $user): void
    {
        DB::transaction(function () use ($tournament, $user): void {
            $tournament->management()->detach($user->getKey());

            ManagerRemovedFromTournament::dispatch($tournament, $user);
        });
    }
}
