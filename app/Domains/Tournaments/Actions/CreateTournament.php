<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Tournaments\Data\CreateTournamentData;
use App\Domains\Tournaments\Events\TournamentCreated;
use App\Domains\Tournaments\Models\Tournament;
use Illuminate\Support\Facades\DB;

/**
 * Create a new tournament in the current club. New tournaments start in 'draft' — they
 * become 'open' only once registration is opened (see OpenRegistration). The
 * TournamentCreated event fires after commit.
 *
 * BelongsToTenant auto-fills `tenant_id` from the active tenant context.
 */
final class CreateTournament
{
    public function handle(CreateTournamentData $data): Tournament
    {
        return DB::transaction(function () use ($data): Tournament {
            $tournament = Tournament::create([
                'name' => $data->name,
                'format' => $data->format,
                'starts_on' => $data->startsOn,
                'ends_on' => $data->endsOn,
                'status' => 'draft',
            ]);

            TournamentCreated::dispatch($tournament);

            return $tournament;
        });
    }
}
