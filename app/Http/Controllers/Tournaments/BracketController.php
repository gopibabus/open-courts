<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tournaments;

use App\Domains\Tournaments\Actions\GenerateBracket;
use App\Domains\Tournaments\Enums\RegistrationStatus;
use App\Domains\Tournaments\Models\TournamentCategory;
use App\Domains\Tournaments\Models\TournamentMatch;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The single-elimination bracket for a tournament category. `show` (the visual bracket) is
 * open to any club member; `generate` is gated by `can:tournament.manage` at the route layer.
 * Bindings resolve through BelongsToTenant, so they're scoped to the club.
 */
class BracketController extends Controller
{
    public function show(TournamentCategory $category): Response
    {
        $category->load('tournament:id,name');

        $matches = TournamentMatch::where('category_id', $category->id)
            ->with(['playerOne:id,name', 'playerTwo:id,name', 'attachments'])
            ->orderBy('position')
            ->get();

        // Group into rounds ordered first → final (more matches = earlier round).
        $rounds = $matches
            ->groupBy(fn (TournamentMatch $m) => $m->round->value)
            ->map(fn (Collection $group) => [
                'name' => $group->first()->round->value,
                'label' => $group->first()->round->label(),
                'matches' => $group->sortBy('position')->map(fn (TournamentMatch $m) => $this->matchPayload($m))->values(),
            ])
            ->sortByDesc(fn (array $round) => count($round['matches']))
            ->values();

        return Inertia::render('tournaments/bracket', [
            'tournament' => ['id' => $category->tournament->id, 'name' => $category->tournament->name],
            'category' => ['id' => $category->id, 'name' => $category->name, 'type' => $category->type->value],
            'rounds' => $rounds,
            'hasBracket' => $matches->isNotEmpty(),
            'canManage' => request()->user()?->can('tournament.manage') ?? false,
        ]);
    }

    public function generate(TournamentCategory $category, GenerateBracket $generate): RedirectResponse
    {
        $confirmed = $category->registrations()
            ->where('status', RegistrationStatus::Confirmed->value)
            ->count();

        if ($confirmed < 2) {
            return back()->withErrors(['bracket' => 'At least 2 confirmed entrants are needed to generate a bracket.']);
        }

        $generate->handle($category);

        return redirect()
            ->route('tournaments.bracket', $category)
            ->with('status', 'Bracket generated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function matchPayload(TournamentMatch $match): array
    {
        return [
            'id' => $match->id,
            'position' => $match->position,
            'round' => $match->round->label(),
            'playerOne' => $match->playerOne ? ['id' => $match->playerOne->id, 'name' => $match->playerOne->name] : null,
            'playerTwo' => $match->playerTwo ? ['id' => $match->playerTwo->id, 'name' => $match->playerTwo->name] : null,
            'winnerId' => $match->winner_id,
            'score' => $match->score,
            'notes' => $match->notes,
            'status' => $match->status,
            'attachments' => $match->attachments->map(fn ($a) => [
                'id' => $a->id,
                'url' => $a->url(),
                'name' => $a->original_name,
            ])->values(),
        ];
    }
}
