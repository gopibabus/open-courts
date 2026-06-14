<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tournaments;

use App\Domains\Identity\Models\User;
use App\Domains\Tournaments\Actions\AddManagerToTournament;
use App\Domains\Tournaments\Actions\RemoveManagerFromTournament;
use App\Domains\Tournaments\Exceptions\ManagementException;
use App\Domains\Tournaments\Models\Tournament;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreTournamentManagerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Manage a tournament's management (the EC). Add or remove the club members who run THIS
 * tournament — the set can differ from tournament to tournament. All actions are guarded
 * by `can:tournament.manage` at the route layer (routes/tenant/tournaments.php).
 *
 * The {tournament} binding resolves through BelongsToTenant, so a manager only ever
 * touches their own club's tournaments.
 */
class ManagementController extends Controller
{
    /**
     * Add a club member to the tournament's management. Domain rejections (not a club
     * member, already on the management) surface as a 422 on `user_id`.
     */
    public function store(StoreTournamentManagerRequest $request, Tournament $tournament, AddManagerToTournament $addManager): RedirectResponse
    {
        $user = User::findOrFail((int) $request->integer('user_id'));

        try {
            $addManager->handle($tournament, $user);
        } catch (ManagementException $e) {
            throw ValidationException::withMessages(['user_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('status', 'Added to the management.');
    }

    public function destroy(Tournament $tournament, User $user, RemoveManagerFromTournament $removeManager): RedirectResponse
    {
        $removeManager->handle($tournament, $user);

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('status', 'Removed from the management.');
    }
}
