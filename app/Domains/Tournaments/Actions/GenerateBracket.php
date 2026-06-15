<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Tournaments\Enums\RegistrationStatus;
use App\Domains\Tournaments\Models\Team;
use App\Domains\Tournaments\Models\TournamentCategory;
use App\Domains\Tournaments\Models\TournamentMatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generate a single-elimination bracket for a category from its confirmed entrants.
 *
 * The field is padded to the next power of two with byes; standard seeding keeps top seeds
 * apart and pairs them against the byes. Matches are created round-by-round (final first) so
 * each links its `next_match` (where the winner advances). Round-1 byes auto-advance.
 *
 * Re-running regenerates from scratch (it deletes the category's existing matches first).
 */
final class GenerateBracket
{
    public function handle(TournamentCategory $category): void
    {
        DB::transaction(function () use ($category): void {
            TournamentMatch::where('category_id', $category->id)->delete();

            // Participants + the columns to seed are either users (singles/doubles) or teams.
            $isTeam = $category->is_team;
            [$oneCol, $twoCol, $winnerCol] = $isTeam
                ? ['team_one_id', 'team_two_id', 'winner_team_id']
                : ['player_one_id', 'player_two_id', 'winner_id'];

            $entrants = $isTeam
                ? Team::where('tournament_id', $category->tournament_id)->orderBy('id')->pluck('id')->all()
                : $category->registrations()
                    ->where('status', RegistrationStatus::Confirmed->value)
                    ->orderByRaw('seed is null, seed')
                    ->orderBy('id')
                    ->pluck('user_id')
                    ->all();

            $size = $this->bracketSize(count($entrants));
            $rounds = (int) round(log($size, 2));

            // Create matches from the final back to round 1, linking each round to the next.
            $next = [];
            $byRound = [];
            for ($r = $rounds; $r >= 1; $r--) {
                $count = intdiv($size, 2 ** $r);
                $name = $this->roundName($count);
                $created = [];
                for ($pos = 0; $pos < $count; $pos++) {
                    $nextMatch = $r < $rounds ? $next[intdiv($pos, 2)] : null;
                    $created[$pos] = TournamentMatch::create([
                        'tournament_id' => $category->tournament_id,
                        'category_id' => $category->id,
                        'round' => $name,
                        'position' => $pos,
                        'next_match_id' => $nextMatch?->id,
                        'next_slot' => $r < $rounds ? ($pos % 2) + 1 : null,
                        'status' => 'scheduled',
                    ]);
                }
                $next = $created;
                $byRound[$r] = $created;
            }

            // Seed entrants into round 1 (standard bracket positions); empty slots are byes.
            $seedOrder = $this->seedOrder($size);
            foreach ($byRound[1] as $pos => $match) {
                $match->update([
                    $oneCol => $entrants[$seedOrder[$pos * 2] - 1] ?? null,
                    $twoCol => $entrants[$seedOrder[$pos * 2 + 1] - 1] ?? null,
                ]);
            }

            // Auto-advance byes — a round-1 match with exactly one participant present.
            foreach ($byRound[1] as $match) {
                $match->refresh();
                $one = $match->{$oneCol};
                $two = $match->{$twoCol};
                if (($one === null) !== ($two === null)) {
                    $this->advanceBye($match, (int) ($one ?? $two), $oneCol, $twoCol, $winnerCol);
                }
            }
        });
    }

    private function advanceBye(TournamentMatch $match, int $winnerId, string $oneCol, string $twoCol, string $winnerCol): void
    {
        $match->update([
            $winnerCol => $winnerId,
            'status' => 'completed',
            'notes' => 'Bye',
            'played_at' => Carbon::now(),
        ]);

        if ($match->next_match_id !== null && $match->next_slot !== null) {
            $column = $match->next_slot === 1 ? $oneCol : $twoCol;
            TournamentMatch::where('id', $match->next_match_id)->update([$column => $winnerId]);
        }
    }

    /** The next power of two that fits the entrants (minimum 2). */
    private function bracketSize(int $entrants): int
    {
        $size = 2;
        while ($size < $entrants) {
            $size *= 2;
        }

        return $size;
    }

    private function roundName(int $matchesInRound): string
    {
        return match ($matchesInRound) {
            1 => 'final',
            2 => 'semi_final',
            4 => 'quarter_final',
            8 => 'round_of_16',
            default => 'other',
        };
    }

    /**
     * Standard single-elimination seed positions (1-indexed) in slot order, length = $size.
     * e.g. size 8 → [1,8,4,5,2,7,3,6].
     *
     * @return array<int, int>
     */
    private function seedOrder(int $size): array
    {
        $rounds = (int) round(log($size, 2));
        $pots = [1, 2];
        for ($r = 1; $r < $rounds; $r++) {
            $len = count($pots) * 2 + 1;
            $nextPots = [];
            foreach ($pots as $seed) {
                $nextPots[] = $seed;
                $nextPots[] = $len - $seed;
            }
            $pots = $nextPots;
        }

        return $pots;
    }
}
