<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tournaments;

use App\Domains\Tournaments\Actions\UpdateWaiverTemplate;
use App\Domains\Tournaments\Models\ClubWaiverTemplate;
use App\Domains\Tournaments\Support\DefaultWaiver;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\WaiverTemplateRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Edit the club's waiver template — the clauses every player signs before competing. Both
 * actions are gated by `can:tournament.manage` at the route layer. Editing the template never
 * changes what past signers agreed to: each signature snapshots its own clauses.
 */
class WaiverTemplateController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('tournaments/waiver-template', [
            // Raw clauses (with the {tournament} placeholder intact) for the editor.
            'clauses' => ClubWaiverTemplate::clausesForClub(),
            'defaults' => DefaultWaiver::clauses(),
            'isCustomised' => ClubWaiverTemplate::current() !== null,
        ]);
    }

    public function update(WaiverTemplateRequest $request, UpdateWaiverTemplate $action): RedirectResponse
    {
        $action->handle($request->toData());

        return redirect()
            ->route('tournaments.waiver-template.edit')
            ->with('status', 'Waiver template saved.');
    }
}
