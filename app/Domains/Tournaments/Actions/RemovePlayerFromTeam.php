<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Tournaments\Events\PlayerRemovedFromTeam;
use App\Domains\Tournaments\Models\Team;
use Illuminate\Support\Facades\DB;

/**
 * Remove a player from a team's roster. Idempotent — detaching a player who is not on the
 * team is a no-op. The PlayerRemovedFromTeam event fires after commit.
 */
final class RemovePlayerFromTeam
{
    public function handle(Team $team, User $player): void
    {
        DB::transaction(function () use ($team, $player): void {
            $team->players()->detach($player->getKey());

            PlayerRemovedFromTeam::dispatch($team, $player);
        });
    }
}
