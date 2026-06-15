<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tournaments;

use App\Domains\Tournaments\Actions\SignWaiver;
use App\Domains\Tournaments\Models\ClubWaiverTemplate;
use App\Domains\Tournaments\Models\Tournament;
use App\Domains\Tournaments\Models\TournamentWaiver;
use App\Domains\Tournaments\Support\DefaultWaiver;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\SignWaiverRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A player's liability waiver for a tournament. Any authenticated club member may view +
 * sign their OWN waiver; the organiser sees who has signed on the tournament page. The
 * {tournament} binding is tenant-scoped. The clauses come from the club's editable template
 * (see WaiverTemplateController), falling back to the platform defaults.
 */
class WaiverController extends Controller
{
    public function show(Tournament $tournament): Response
    {
        $user = auth()->user();
        $waiver = TournamentWaiver::where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)
            ->first();

        // Show what the player actually agreed to (the snapshot) once signed; otherwise the
        // club's current template, with {tournament} resolved to this tournament's name.
        $waiverText = $waiver?->signed_clauses
            ?? DefaultWaiver::resolve(ClubWaiverTemplate::clausesForClub(), $tournament->name);

        return Inertia::render('tournaments/waiver', [
            'tournament' => ['id' => $tournament->id, 'name' => $tournament->name],
            'memberName' => $user->name,
            'waiverText' => $waiverText,
            'signed' => $waiver
                ? ['signature' => $waiver->signature, 'signedAt' => $waiver->signed_at->toIso8601String()]
                : null,
        ]);
    }

    public function store(SignWaiverRequest $request, Tournament $tournament, SignWaiver $sign): RedirectResponse
    {
        $sign->handle($request->toData());

        return redirect()
            ->route('tournaments.waiver', $tournament)
            ->with('status', 'Waiver signed — thank you!');
    }
}
