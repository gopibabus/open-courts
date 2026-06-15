<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tournaments;

use App\Domains\Tournaments\Actions\SignWaiver;
use App\Domains\Tournaments\Models\Tournament;
use App\Domains\Tournaments\Models\TournamentWaiver;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\SignWaiverRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A player's liability waiver for a tournament. Any authenticated club member may view +
 * sign their OWN waiver; the organiser sees who has signed on the tournament page. The
 * {tournament} binding is tenant-scoped.
 */
class WaiverController extends Controller
{
    public function show(Tournament $tournament): Response
    {
        $user = auth()->user();
        $waiver = TournamentWaiver::where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)
            ->first();

        return Inertia::render('tournaments/waiver', [
            'tournament' => ['id' => $tournament->id, 'name' => $tournament->name],
            'memberName' => $user->name,
            'waiverText' => $this->waiverText($tournament->name),
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

    /**
     * The waiver clauses a player agrees to. A sensible default — clubs would tailor this.
     *
     * @return array<int, string>
     */
    private function waiverText(string $tournamentName): array
    {
        return [
            "I am voluntarily entering {$tournamentName} and understand that tennis carries inherent risks of injury.",
            'I confirm I am medically fit to compete and will stop and seek help if I feel unwell.',
            'I release the club, its organisers and volunteers from liability for any injury, loss or damage sustained while participating, to the extent permitted by law.',
            'I consent to photos or video taken at the event being used for club communications.',
        ];
    }
}
