<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tournaments;

use App\Domains\Tournaments\Actions\CreateTeam;
use App\Domains\Tournaments\Data\CreateTeamData;
use App\Domains\Tournaments\Models\Team;
use App\Domains\Tournaments\Models\Tournament;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\TeamsStoreTeamRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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
 * A team belongs to a tournament (teams are created under a tournament), and a club
 * member may be on only ONE team per tournament — enforced in AddPlayerToTeam and by a
 * unique index. The roster picker on `show` excludes anyone already on a team in this
 * tournament.
 */
class TeamController extends Controller
{
    public function index(): Response
    {
        $teams = Team::query()
            ->withCount('players')
            ->with('tournament:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'players_count' => $team->players_count,
                'tournament' => $team->tournament ? ['id' => $team->tournament->id, 'name' => $team->tournament->name] : null,
            ]);

        return Inertia::render('teams/index', [
            'teams' => $teams,
            'canManage' => $this->canManage(),
        ]);
    }

    public function store(TeamsStoreTeamRequest $request, Tournament $tournament, CreateTeam $createTeam): RedirectResponse
    {
        $team = $createTeam->handle(new CreateTeamData(
            name: (string) $request->string('name'),
            tournamentId: (int) $tournament->getKey(),
        ));

        return redirect()
            ->route('teams.show', $team)
            ->with('status', 'Team created.');
    }

    public function show(Team $team): Response
    {
        $team->load(['players' => fn ($q) => $q->orderBy('name'), 'tournament:id,name']);

        // One team per member per tournament: the picker excludes everyone already on ANY
        // team in this tournament (its own roster shows below; others are unavailable).
        $takenIds = DB::table('team_player')->where('tournament_id', $team->tournament_id)->pluck('user_id');
        $availableMembers = $team->tenant()
            ->firstOrFail()
            ->users()
            ->whereNotIn('users.id', $takenIds)
            ->orderBy('name')
            ->get(['users.id', 'users.name'])
            ->map(fn ($user) => ['id' => $user->id, 'name' => $user->name]);

        return Inertia::render('teams/show', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'tournament' => $team->tournament ? ['id' => $team->tournament->id, 'name' => $team->tournament->name] : null,
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
