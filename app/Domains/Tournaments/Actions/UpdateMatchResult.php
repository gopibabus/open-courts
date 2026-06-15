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
            $match->score = $score;
            $match->notes = $notes;

            if ($winnerId !== null) {
                $match->winner_id = $winnerId;
                $match->status = 'completed';
                $match->played_at = Carbon::now();
            }

            $match->save();

            // Advance the winner into the next bracket match.
            if ($winnerId !== null && $match->next_match_id !== null && $match->next_slot !== null) {
                $column = $match->next_slot === 1 ? 'player_one_id' : 'player_two_id';
                TournamentMatch::where('id', $match->next_match_id)->update([$column => $winnerId]);
            }

            if ($winnerId !== null) {
                MatchRecorded::dispatch($match);
            }

            return $match;
        });
    }
}
