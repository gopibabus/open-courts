<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Tournaments\Events\MatchRecorded;
use App\Domains\Tournaments\Models\TournamentMatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Update an existing (bracket) match: set its score + notes, and — if a winner is given —
 * mark it completed and advance the winner into the next round's slot. MatchRecorded fires
 * only when a winner is recorded.
 */
final class UpdateMatchResult
{
    public function handle(TournamentMatch $match, ?int $winnerId, ?string $score, ?string $notes): TournamentMatch
    {
        return DB::transaction(function () use ($match, $winnerId, $score, $notes): TournamentMatch {
            // The winner id is a team id for team events, otherwise a user id.
            $isTeam = $match->category?->is_team ?? false;
            [$oneCol, $twoCol, $winnerCol] = $isTeam
                ? ['team_one_id', 'team_two_id', 'winner_team_id']
                : ['player_one_id', 'player_two_id', 'winner_id'];

            $match->score = $score;
            $match->notes = $notes;

            if ($winnerId !== null) {
                $match->{$winnerCol} = $winnerId;
                $match->status = 'completed';
                $match->played_at = Carbon::now();
            }

            $match->save();

            // Advance the winner into the next bracket match.
            if ($winnerId !== null && $match->next_match_id !== null && $match->next_slot !== null) {
                $column = $match->next_slot === 1 ? $oneCol : $twoCol;
                TournamentMatch::where('id', $match->next_match_id)->update([$column => $winnerId]);
            }

            if ($winnerId !== null) {
                MatchRecorded::dispatch($match);
            }

            return $match;
        });
    }
}
