<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tournaments;

use App\Domains\Tournaments\Actions\CreateTeam;
use App\Domains\Tournaments\Models\Team;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\TeamsStoreTeamRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Team (squad) management within a club — part of the Tournaments bounded context.
 *
 * Index/show are open to any authenticated club member; store/destroy and all roster
 * mutations are guarded by `can:team.manage` at the route layer (routes/tenant/teams.php).
 *
 * Tenant scoping is automatic: Team uses BelongsToTenant, so every query and the
 * {team} route-model binding are limited to the current club.
 *
 * OUT OF SCOPE for this slice: linking a team to specific tournament categories /
 * registrations — a later slice. `teams.tournament_id` (nullable) already lets a team
 * optionally belong to a tournament.
 */
class TeamController extends Controller
{
    public function index(): Response
    {
        $teams = Team::query()
            ->withCount('players')
            ->orderBy('name')
            ->get()
            ->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'players_count' => $team->players_count,
            ]);

        return Inertia::render('teams/index', [
            'teams' => $teams,
            'canManage' => $this->canManage(),
        ]);
    }

    public function store(TeamsStoreTeamRequest $request, CreateTeam $createTeam): RedirectResponse
    {
        $team = $createTeam->handle($request->toData());

        return redirect()
            ->route('teams.show', $team)
            ->with('status', 'Team created.');
    }

    public function show(Team $team): Response
    {
        $team->load(['players' => fn ($q) => $q->orderBy('name')]);

        // Members of the club who are not yet on this team — the "add player" picker.
        $rosterIds = $team->players->pluck('id');
        $availableMembers = $team->tenant()
            ->firstOrFail()
            ->users()
            ->whereNotIn('users.id', $rosterIds)
            ->orderBy('name')
            ->get(['users.id', 'users.name'])
            ->map(fn ($user) => ['id' => $user->id, 'name' => $user->name]);

        return Inertia::render('teams/show', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'tournament_id' => $team->tournament_id,
            ],
            'roster' => $team->players->map(fn ($player) => [
                'id' => $player->id,
                'name' => $player->name,
            ])->values(),
            'availableMembers' => $availableMembers->values(),
            'canManage' => $this->canManage(),
        ]);
    }

    public function destroy(Team $team): RedirectResponse
    {
        $team->delete();

        return redirect()
            ->route('teams.index')
            ->with('status', 'Team deleted.');
    }

    private function canManage(): bool
    {
        return request()->user()?->can('team.manage') ?? false;
    }
}
