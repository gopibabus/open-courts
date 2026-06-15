<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Tournaments\Enums\RegistrationStatus;
use App\Domains\Tournaments\Models\TournamentCategory;
use App\Domains\Tournaments\Models\TournamentMatch;

/**
 * Derive a round-robin standings table for a category from its COMPLETED matches: one row
 * per confirmed entrant with played / won / lost and points (1 per win), ranked by points
 * then wins then name. Must run inside the club's tenancy context.
 */
final class BuildStandings
{
    /**
     * @return array<int, array{userId: int, name: string|null, played: int, won: int, lost: int, points: int}>
     */
    public function handle(TournamentCategory $category): array
    {
        $matches = TournamentMatch::where('category_id', $category->id)
            ->whereNotNull('winner_id')
            ->get();

        $rows = $category->registrations()
            ->with('user:id,name')
            ->where('status', RegistrationStatus::Confirmed->value)
            ->get()
            ->map(function ($registration) use ($matches): array {
                $userId = (int) $registration->user_id;
                $played = $matches->filter(fn (TournamentMatch $m) => $m->player_one_id === $userId || $m->player_two_id === $userId)->count();
                $won = $matches->where('winner_id', $userId)->count();

                return [
                    'userId' => $userId,
                    'name' => $registration->user?->name,
                    'played' => $played,
                    'won' => $won,
                    'lost' => $played - $won,
                    'points' => $won,
                ];
            })
            ->all();

        usort($rows, function (array $a, array $b): int {
            return [$b['points'], $b['won']] <=> [$a['points'], $a['won']]
                ?: strcmp((string) $a['name'], (string) $b['name']);
        });

        return $rows;
    }
}
