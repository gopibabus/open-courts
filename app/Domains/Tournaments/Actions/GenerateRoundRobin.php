<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Tournaments\Enums\RegistrationStatus;
use App\Domains\Tournaments\Models\Team;
use App\Domains\Tournaments\Models\TournamentCategory;
use App\Domains\Tournaments\Models\TournamentMatch;
use Illuminate\Support\Facades\DB;

/**
 * Generate a round-robin schedule for a category: every confirmed entrant plays every other
 * once. Matches are `group`-round fixtures with no advancement (next_match is null); the
 * standings are derived from the completed ones (see BuildStandings).
 *
 * Re-running regenerates from scratch (it deletes the category's existing matches first).
 */
final class GenerateRoundRobin
{
    public function handle(TournamentCategory $category): void
    {
        DB::transaction(function () use ($category): void {
            TournamentMatch::where('category_id', $category->id)->delete();

            $isTeam = $category->is_team;
            [$oneCol, $twoCol] = $isTeam ? ['team_one_id', 'team_two_id'] : ['player_one_id', 'player_two_id'];

            $entrants = $isTeam
                ? Team::where('tournament_id', $category->tournament_id)->orderBy('id')->pluck('id')->all()
                : $category->registrations()
                    ->where('status', RegistrationStatus::Confirmed->value)
                    ->orderByRaw('seed is null, seed')
                    ->orderBy('id')
                    ->pluck('user_id')
                    ->all();

            $position = 0;
            $count = count($entrants);
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    TournamentMatch::create([
                        'tournament_id' => $category->tournament_id,
                        'category_id' => $category->id,
                        'round' => 'group',
                        'position' => $position++,
                        $oneCol => $entrants[$i],
                        $twoCol => $entrants[$j],
                        'status' => 'scheduled',
                    ]);
                }
            }
        });
    }
}
