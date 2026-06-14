<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tournaments;

use App\Domains\Identity\Models\User;
use App\Domains\Tournaments\Actions\AddPlayerToTeam;
use App\Domains\Tournaments\Actions\RemovePlayerFromTeam;
use App\Domains\Tournaments\Exceptions\RosterException;
use App\Domains\Tournaments\Models\Team;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\TeamsStoreRosterPlayerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Manage a team's roster (which club members play on the team). All actions require
 * `can:team.manage` (guarded at the route layer, routes/tenant/teams.php).
 *
 * The {team} and {player} bindings resolve through BelongsToTenant (User has no tenant
 * scope, but membership is re-checked in AddPlayerToTeam), so a manager can only ever
 * touch their own club's teams.
 */
class RosterController extends Controller
{
    /**
     * Add a club member to the team. Domain rejections (not a club member, already on the
     * team) surface as a 422 validation error on `user_id`.
     */
    public function store(
        TeamsStoreRosterPlayerRequest $request,
        Team $team,
        AddPlayerToTeam $addPlayerToTeam,
    ): RedirectResponse {
        $player = User::findOrFail((int) $request->integer('user_id'));

        try {
            $addPlayerToTeam->handle($team, $player);
        } catch (RosterException $e) {
            throw ValidationException::withMessages(['user_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('teams.show', $team)
            ->with('status', 'Player added to the team.');
    }

    /**
     * Remove a player from the team roster.
     */
    public function destroy(
        Team $team,
        User $player,
        RemovePlayerFromTeam $removePlayerFromTeam,
    ): RedirectResponse {
        $removePlayerFromTeam->handle($team, $player);

        return redirect()
            ->route('teams.show', $team)
            ->with('status', 'Player removed from the team.');
    }
}
