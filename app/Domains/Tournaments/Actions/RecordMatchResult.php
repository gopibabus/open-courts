<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Tournaments\Data\RecordMatchResultData;
use App\Domains\Tournaments\Events\MatchRecorded;
use App\Domains\Tournaments\Models\TournamentMatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Record a completed singles match result for the current club. tenant_id is filled
 * automatically by BelongsToTenant from the active tenancy context. The MatchRecorded
 * event fires after commit.
 */
final class RecordMatchResult
{
    public function handle(RecordMatchResultData $data): TournamentMatch
    {
        return DB::transaction(function () use ($data): TournamentMatch {
            $match = TournamentMatch::create([
                'tournament_id' => $data->tournamentId,
                'category_id' => $data->categoryId,
                'round' => $data->round,
                'player_one_id' => $data->playerOneId,
                'player_two_id' => $data->playerTwoId,
                'winner_id' => $data->winnerId,
                'score' => $data->score,
                'status' => 'completed',
                'played_at' => Carbon::now(),
            ]);

            MatchRecorded::dispatch($match);

            return $match;
        });
    }
}
