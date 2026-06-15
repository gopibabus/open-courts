<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tournaments;

use App\Domains\Tournaments\Actions\BuildStandings;
use App\Domains\Tournaments\Actions\GenerateBracket;
use App\Domains\Tournaments\Actions\GenerateRoundRobin;
use App\Domains\Tournaments\Enums\RegistrationStatus;
use App\Domains\Tournaments\Enums\TournamentFormat;
use App\Domains\Tournaments\Models\TournamentCategory;
use App\Domains\Tournaments\Models\TournamentMatch;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A tournament category's draw — a single-elimination bracket OR a round-robin group +
 * standings, depending on the category's format. `show` is open to any club member;
 * `generate` is gated by `can:tournament.manage` at the route layer. Bindings resolve
 * through BelongsToTenant, so they're scoped to the club.
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

        // For doubles/mixed, each side is a pair — map the entrant's user to their partner's
        // name so the draw can show "Player & Partner". Empty for singles.
        $partners = $category->registrations()
            ->with('partner:id,name')
            ->get()
            ->mapWithKeys(fn ($r) => [(int) $r->user_id => $r->partner?->name])
            ->all();

        $isRoundRobin = $category->format === TournamentFormat::RoundRobin;

        return Inertia::render('tournaments/bracket', [
            'tournament' => ['id' => $category->tournament->id, 'name' => $category->tournament->name],
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type->value,
                'format' => $category->format->value,
            ],
            'hasSchedule' => $matches->isNotEmpty(),
            'canManage' => request()->user()?->can('tournament.manage') ?? false,

            // Single-elimination: matches grouped into rounds (first → final).
            'rounds' => $isRoundRobin ? [] : $matches
                ->groupBy(fn (TournamentMatch $m) => $m->round->value)
                ->map(fn (Collection $group) => [
                    'name' => $group->first()->round->value,
                    'label' => $group->first()->round->label(),
                    'matches' => $group->sortBy('position')->map(fn (TournamentMatch $m) => $this->matchPayload($m, $partners))->values(),
                ])
                ->sortByDesc(fn (array $round) => count($round['matches']))
                ->values(),

            // Round-robin: a standings table + the full fixture list.
            'standings' => $isRoundRobin ? app(BuildStandings::class)->handle($category) : [],
            'fixtures' => $isRoundRobin ? $matches->map(fn (TournamentMatch $m) => $this->matchPayload($m, $partners))->values() : [],
        ]);
    }

    public function generate(TournamentCategory $category, GenerateBracket $bracket, GenerateRoundRobin $roundRobin): RedirectResponse
    {
        $confirmed = $category->registrations()
            ->where('status', RegistrationStatus::Confirmed->value)
            ->count();

        if ($confirmed < 2) {
            return back()->withErrors(['bracket' => 'At least 2 confirmed entrants are needed to generate the draw.']);
        }

        if ($category->format === TournamentFormat::RoundRobin) {
            $roundRobin->handle($category);
        } else {
            $bracket->handle($category);
        }

        return redirect()
            ->route('tournaments.bracket', $category)
            ->with('status', 'Draw generated.');
    }

    /**
     * @param  array<int, string|null>  $partners  entrant user id → partner name (doubles)
     * @return array<string, mixed>
     */
    private function matchPayload(TournamentMatch $match, array $partners = []): array
    {
        return [
            'id' => $match->id,
            'position' => $match->position,
            'round' => $match->round->label(),
            'playerOne' => $match->playerOne
                ? ['id' => $match->playerOne->id, 'name' => $match->playerOne->name, 'partner' => $partners[$match->player_one_id] ?? null]
                : null,
            'playerTwo' => $match->playerTwo
                ? ['id' => $match->playerTwo->id, 'name' => $match->playerTwo->name, 'partner' => $partners[$match->player_two_id] ?? null]
                : null,
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
