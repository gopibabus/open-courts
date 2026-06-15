<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tournaments;

use App\Domains\Tournaments\Actions\RecordMatchResult;
use App\Domains\Tournaments\Models\Tournament;
use App\Domains\Tournaments\Models\TournamentMatch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\RecordMatchRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Recording match results within a tournament — part of the Tournaments bounded context.
 * Both endpoints are gated by `can:tournament.manage` at the route layer. The {tournament}
 * and {match} bindings resolve through BelongsToTenant, so they are scoped to the club.
 */
class MatchController extends Controller
{
    public function store(RecordMatchRequest $request, Tournament $tournament, RecordMatchResult $record): RedirectResponse
    {
        $record->handle($request->toData());

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('status', 'Result recorded.');
    }

    public function destroy(TournamentMatch $match): RedirectResponse
    {
        $tournamentId = $match->tournament_id;
        $match->delete();

        return redirect()
            ->route('tournaments.show', $tournamentId)
            ->with('status', 'Result removed.');
    }
}
