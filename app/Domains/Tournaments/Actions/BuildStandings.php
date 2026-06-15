<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Tournaments\Enums\RegistrationStatus;
use App\Domains\Tournaments\Models\Team;
use App\Domains\Tournaments\Models\TournamentCategory;
use App\Domains\Tournaments\Models\TournamentMatch;

/**
 * Derive a round-robin standings table for a category from its COMPLETED matches: one row
 * per participant (entrant, or team for a team event) with played / won / lost and points
 * (1 per win), ranked by points then wins then name. Must run inside the club's tenancy.
 */
final class BuildStandings
{
    /**
     * @return array<int, array{userId: int, name: string|null, partner: string|null, played: int, won: int, lost: int, points: int}>
     */
    public function handle(TournamentCategory $category): array
    {
        $rows = $category->is_team ? $this->teamRows($category) : $this->entrantRows($category);

        usort($rows, function (array $a, array $b): int {
            return [$b['points'], $b['won']] <=> [$a['points'], $a['won']]
                ?: strcmp((string) $a['name'], (string) $b['name']);
        });

        return $rows;
    }

    /** @return array<int, array<string, mixed>> */
    private function entrantRows(TournamentCategory $category): array
    {
        $matches = TournamentMatch::where('category_id', $category->id)->whereNotNull('winner_id')->get();

        return $category->registrations()
            ->with(['user:id,name', 'partner:id,name'])
            ->where('status', RegistrationStatus::Confirmed->value)
            ->get()
            ->map(function ($registration) use ($matches): array {
                $userId = (int) $registration->user_id;
                $played = $matches->filter(fn (TournamentMatch $m) => $m->player_one_id === $userId || $m->player_two_id === $userId)->count();
                $won = $matches->where('winner_id', $userId)->count();

                return [
                    'userId' => $userId,
                    'name' => $registration->user?->name,
                    'partner' => $registration->partner?->name,
                    'played' => $played,
                    'won' => $won,
                    'lost' => $played - $won,
                    'points' => $won,
                ];
            })
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function teamRows(TournamentCategory $category): array
    {
        $matches = TournamentMatch::where('category_id', $category->id)->whereNotNull('winner_team_id')->get();

        return Team::where('tournament_id', $category->tournament_id)
            ->get(['id', 'name'])
            ->map(function (Team $team) use ($matches): array {
                $teamId = (int) $team->id;
                $played = $matches->filter(fn (TournamentMatch $m) => $m->team_one_id === $teamId || $m->team_two_id === $teamId)->count();
                $won = $matches->where('winner_team_id', $teamId)->count();

                return [
                    'userId' => $teamId,
                    'name' => $team->name,
                    'partner' => null,
                    'played' => $played,
                    'won' => $won,
                    'lost' => $played - $won,
                    'points' => $won,
                ];
            })
            ->all();
    }
}
