<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Tournaments\Events\PlayerAddedToTeam;
use App\Domains\Tournaments\Exceptions\RosterException;
use App\Domains\Tournaments\Models\Team;
use Illuminate\Support\Facades\DB;

/**
 * Add a club member to a team's roster. Enforces two domain rules before persisting
 * (throwing RosterException on failure):
 *
 *   1. The user must be a member of this club (in the team's tenant).
 *   2. No duplicate — the user is not already on the team.
 *
 * IMPORTANT: attach() writes the pivot row with a raw insert that bypasses model events,
 * so BelongsToTenant cannot auto-fill `tenant_id` — we pass it explicitly on the pivot.
 *
 * The PlayerAddedToTeam event fires after commit.
 */
final class AddPlayerToTeam
{
    public function handle(Team $team, User $player): User
    {
        return DB::transaction(function () use ($team, $player): User {
            $this->assertIsClubMember($team, $player);
            $this->assertNotAlreadyOnTeam($team, $player);

            // attach() bypasses model events; stamp the pivot tenant_id by hand.
            $team->players()->attach($player->getKey(), ['tenant_id' => $team->tenant_id]);

            PlayerAddedToTeam::dispatch($team, $player);

            return $player;
        });
    }

    private function assertIsClubMember(Team $team, User $player): void
    {
        $isMember = $team->tenant()
            ->firstOrFail()
            ->users()
            ->whereKey($player->getKey())
            ->exists();

        if (! $isMember) {
            throw RosterException::notAMember();
        }
    }

    private function assertNotAlreadyOnTeam(Team $team, User $player): void
    {
        if ($team->hasPlayer($player)) {
            throw RosterException::duplicate();
        }
    }
}
