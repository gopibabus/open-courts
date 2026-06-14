<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tournaments;

use App\Domains\Tournaments\Actions\CreateTournament;
use App\Domains\Tournaments\Actions\OpenRegistration;
use App\Domains\Tournaments\Enums\CategoryType;
use App\Domains\Tournaments\Enums\TournamentFormat;
use App\Domains\Tournaments\Models\Tournament;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\OpenRegistrationRequest;
use App\Http\Requests\Tournaments\StoreTournamentRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tournament management within a club. Index/show are open to any club member; create/store
 * are guarded by `can:tournament.manage` at the route layer (routes/tenant/tournaments.php).
 *
 * Tenant scoping is automatic: Tournament uses BelongsToTenant, so every query here is
 * limited to the current club.
 */
class TournamentController extends Controller
{
    public function index(): Response
    {
        $tournaments = Tournament::query()
            ->withCount('categories')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Tournament $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'status' => $t->status,
                'format' => $t->format->value,
                'starts_on' => $t->starts_on?->toDateString(),
                'ends_on' => $t->ends_on?->toDateString(),
                'registration_opens_on' => $t->registration_opens_on?->toDateString(),
                'registration_closes_on' => $t->registration_closes_on?->toDateString(),
                'categories_count' => $t->categories_count,
            ]);

        return Inertia::render('tournaments/index', [
            'tournaments' => $tournaments,
            'canManage' => $this->canManage(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('tournaments/create', [
            'formats' => $this->formatOptions(),
        ]);
    }

    public function store(StoreTournamentRequest $request, CreateTournament $createTournament): RedirectResponse
    {
        $tournament = $createTournament->handle($request->toData());

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('status', 'Tournament created.');
    }

    /**
     * Open registration for a tournament (set the window, flip status to 'open').
     * Guarded by `can:tournament.manage` at the route layer.
     */
    public function openRegistration(
        OpenRegistrationRequest $request,
        Tournament $tournament,
        OpenRegistration $openRegistration,
    ): RedirectResponse {
        $openRegistration->handle($tournament, $request->toData());

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('status', 'Registration is now open.');
    }

    public function show(Tournament $tournament): Response
    {
        $tournament->load([
            'categories' => fn ($q) => $q->orderBy('name'),
            'categories.registrations.user:id,name',
            'categories.registrations.partner:id,name',
            'teams' => fn ($q) => $q->withCount('players')->orderBy('name'),
            'management:id,name',
        ]);

        return Inertia::render('tournaments/show', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'status' => $tournament->status,
                'format' => $tournament->format->value,
                'starts_on' => $tournament->starts_on?->toDateString(),
                'ends_on' => $tournament->ends_on?->toDateString(),
                'registration_opens_on' => $tournament->registration_opens_on?->toDateString(),
                'registration_closes_on' => $tournament->registration_closes_on?->toDateString(),
            ],
            'categories' => $tournament->categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'type' => $c->type->value,
                'max_entrants' => $c->max_entrants,
                'entrants' => $c->registrations->map(fn ($r) => [
                    'id' => $r->id,
                    'user' => $r->user?->only(['id', 'name']),
                    'partner' => $r->partner?->only(['id', 'name']),
                    'seed' => $r->seed,
                    'status' => $r->status->value,
                ])->values(),
            ])->values(),
            // Tournament squads (teams). Each member may be on only one team per tournament.
            'teams' => $tournament->teams->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'players_count' => $t->players_count,
            ])->values(),
            // The EC (management) — club members who run this tournament.
            'management' => $tournament->management->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ])->values(),
            // Club members not yet on the EC — the "add to management" picker.
            'availableManagers' => tenant()->users()
                ->whereNotIn('users.id', $tournament->management->pluck('id'))
                ->orderBy('name')
                ->get(['users.id', 'users.name'])
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
                ->values(),
            'categoryTypes' => $this->categoryTypeOptions(),
            'canManage' => $this->canManage(),
            // Creating teams is gated by team.manage (coach + club-admin), separate from
            // tournament.manage which gates categories/registration/EC.
            'canManageTeams' => request()->user()?->can('team.manage') ?? false,
        ]);
    }

    private function canManage(): bool
    {
        return request()->user()?->can('tournament.manage') ?? false;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function formatOptions(): array
    {
        return [
            ['value' => TournamentFormat::SingleElimination->value, 'label' => 'Single elimination'],
            ['value' => TournamentFormat::RoundRobin->value, 'label' => 'Round robin'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function categoryTypeOptions(): array
    {
        return [
            ['value' => CategoryType::Singles->value, 'label' => 'Singles'],
            ['value' => CategoryType::Doubles->value, 'label' => 'Doubles'],
            ['value' => CategoryType::Mixed->value, 'label' => 'Mixed'],
        ];
    }
}
