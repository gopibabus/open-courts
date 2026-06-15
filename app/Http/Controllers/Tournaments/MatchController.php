<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tournaments;

use App\Domains\Tournaments\Actions\RecordMatchResult;
use App\Domains\Tournaments\Actions\UpdateMatchResult;
use App\Domains\Tournaments\Actions\UploadMatchImage;
use App\Domains\Tournaments\Models\MatchAttachment;
use App\Domains\Tournaments\Models\Tournament;
use App\Domains\Tournaments\Models\TournamentMatch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\RecordMatchRequest;
use App\Http\Requests\Tournaments\StoreMatchImageRequest;
use App\Http\Requests\Tournaments\UpdateMatchRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

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

    /** Update an existing bracket match's score / winner / notes (and advance the winner). */
    public function update(UpdateMatchRequest $request, TournamentMatch $match, UpdateMatchResult $update): RedirectResponse
    {
        $update->handle(
            $match,
            $request->filled('winner_id') ? (int) $request->integer('winner_id') : null,
            $request->filled('score') ? (string) $request->string('score') : null,
            $request->filled('notes') ? (string) $request->string('notes') : null,
        );

        return back()->with('status', 'Match updated.');
    }

    /** Attach an uploaded image to a match. */
    public function storeAttachment(StoreMatchImageRequest $request, TournamentMatch $match, UploadMatchImage $upload): RedirectResponse
    {
        $upload->handle($match, $request->file('image'), $request->user()?->id);

        return back()->with('status', 'Image uploaded.');
    }

    public function destroyAttachment(MatchAttachment $attachment): RedirectResponse
    {
        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return back();
    }
}
