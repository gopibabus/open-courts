<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Tournaments\Events\PlayerAddedToTeam;
use App\Domains\Tournaments\Exceptions\RosterException;
use App\Domains\Tournaments\Models\Team;
use Illuminate\Support\Facades\DB;

/**
 * Add a club member to a team's roster. Enforces three domain rules before persisting
 * (throwing RosterException on failure):
 *
 *   1. The user must be a member of this club (in the team's tenant).
 *   2. No duplicate — the user is not already on the team.
 *   3. One team per tournament — the user is not on another team in the same tournament.
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
            $this->assertNotAlreadyInTournament($team, $player);

            // attach() bypasses model events; stamp tenant_id + tournament_id by hand.
            // tournament_id on the pivot backs the "one team per member per tournament"
            // unique index.
            $team->players()->attach($player->getKey(), [
                'tenant_id' => $team->tenant_id,
                'tournament_id' => $team->tournament_id,
            ]);

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

    /**
     * A member may be on only ONE team per tournament. Reject if they already play for a
     * different team in the same tournament. (Across different tournaments is fine.)
     */
    private function assertNotAlreadyInTournament(Team $team, User $player): void
    {
        $onAnotherTeam = Team::query()
            ->where('tournament_id', $team->tournament_id)
            ->whereKeyNot($team->getKey())
            ->whereHas('players', fn ($q) => $q->whereKey($player->getKey()))
            ->exists();

        if ($onAnotherTeam) {
            throw RosterException::alreadyInTournament();
        }
    }
}
