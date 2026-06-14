<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tournaments;

use App\Domains\Tournaments\Actions\RegisterEntrant;
use App\Domains\Tournaments\Data\RegisterEntrantData;
use App\Domains\Tournaments\Enums\RegistrationStatus;
use App\Domains\Tournaments\Exceptions\RegistrationException;
use App\Domains\Tournaments\Models\Registration;
use App\Domains\Tournaments\Models\TournamentCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreRegistrationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Entrant self-registration. Any authenticated club member may register themselves into a
 * category (no `tournament.manage` needed) and withdraw their own registration.
 *
 * The {category} / {registration} bindings are tenant-scoped (BelongsToTenant), so a member
 * can only ever touch their own club's data.
 */
class RegistrationController extends Controller
{
    /**
     * Register the current user into a category. Domain rejections (closed window, at
     * capacity, duplicate) surface as a 422 validation error on `registration`.
     */
    public function store(
        StoreRegistrationRequest $request,
        TournamentCategory $category,
        RegisterEntrant $registerEntrant,
    ): RedirectResponse {
        $partnerId = $request->filled('partner_id') ? (int) $request->integer('partner_id') : null;

        try {
            $registerEntrant->handle($category, new RegisterEntrantData(
                userId: (int) $request->user()->getKey(),
                partnerId: $partnerId,
            ));
        } catch (RegistrationException $e) {
            throw ValidationException::withMessages(['registration' => $e->getMessage()]);
        }

        return redirect()
            ->route('tournaments.show', $category->tournament_id)
            ->with('status', 'You are registered.');
    }

    /**
     * Withdraw a registration. A member may only withdraw their own; managers may withdraw any.
     */
    public function destroy(Registration $registration): RedirectResponse
    {
        $user = request()->user();

        abort_unless(
            $registration->user_id === $user->getKey() || $user->can('tournament.manage'),
            403,
        );

        $registration->update(['status' => RegistrationStatus::Withdrawn]);

        return redirect()
            ->route('tournaments.show', $registration->tournament_id)
            ->with('status', 'Registration withdrawn.');
    }
}
