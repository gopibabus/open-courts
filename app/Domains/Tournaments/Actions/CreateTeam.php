<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Tournaments\Data\CreateTeamData;
use App\Domains\Tournaments\Events\TeamCreated;
use App\Domains\Tournaments\Models\Team;
use Illuminate\Support\Facades\DB;

/**
 * Create a team (squad) in the current club. `tournament_id` is optional — a team can
 * exist before being entered into a tournament draw (a later slice). The TeamCreated
 * event fires after commit.
 *
 * BelongsToTenant auto-fills `tenant_id` from the active tenant context.
 */
final class CreateTeam
{
    public function handle(CreateTeamData $data): Team
    {
        return DB::transaction(function () use ($data): Team {
            $team = Team::create([
                'name' => $data->name,
                'tournament_id' => $data->tournamentId,
            ]);

            TeamCreated::dispatch($team);

            return $team;
        });
    }
}
