<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Tournaments\Data\RegisterEntrantData;
use App\Domains\Tournaments\Enums\RegistrationStatus;
use App\Domains\Tournaments\Events\EntrantRegistered;
use App\Domains\Tournaments\Exceptions\RegistrationException;
use App\Domains\Tournaments\Models\Registration;
use App\Domains\Tournaments\Models\Tournament;
use App\Domains\Tournaments\Models\TournamentCategory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Register an entrant into a tournament category. Enforces three domain rules before
 * persisting (throwing RegistrationException on any failure):
 *
 *   1. The registration window is open  — tournament status 'open' AND today within
 *      [registration_opens_on, registration_closes_on] (a null close date = open-ended).
 *   2. The category is not at capacity   — when `max_entrants` is set, active (non-withdrawn)
 *      registrations must be below it.
 *   3. No duplicate                      — the entrant is not already registered for the category.
 *
 * The EntrantRegistered event fires after commit. New registrations start 'pending'.
 */
final class RegisterEntrant
{
    public function handle(TournamentCategory $category, RegisterEntrantData $data): Registration
    {
        return DB::transaction(function () use ($category, $data): Registration {
            $tournament = $category->tournament()->firstOrFail();

            $this->assertWindowOpen($tournament);
            $this->assertNotDuplicate($category, $data->userId);
            $this->assertHasCapacity($category);

            $registration = $category->registrations()->create([
                'tournament_id' => $tournament->getKey(),
                'user_id' => $data->userId,
                'partner_id' => $data->partnerId,
                'status' => RegistrationStatus::Pending,
            ]);

            EntrantRegistered::dispatch($registration);

            return $registration;
        });
    }

    private function assertWindowOpen(Tournament $tournament): void
    {
        if ($tournament->status !== 'open') {
            throw RegistrationException::windowClosed();
        }

        $today = Carbon::today();

        if ($tournament->registration_opens_on !== null && $today->lt($tournament->registration_opens_on)) {
            throw RegistrationException::windowClosed();
        }

        if ($tournament->registration_closes_on !== null && $today->gt($tournament->registration_closes_on)) {
            throw RegistrationException::windowClosed();
        }
    }

    private function assertNotDuplicate(TournamentCategory $category, int $userId): void
    {
        $exists = $category->registrations()
            ->where('user_id', $userId)
            ->exists();

        if ($exists) {
            throw RegistrationException::duplicate();
        }
    }

    private function assertHasCapacity(TournamentCategory $category): void
    {
        if ($category->max_entrants === null) {
            return;
        }

        $active = $category->registrations()
            ->where('status', '!=', RegistrationStatus::Withdrawn->value)
            ->count();

        if ($active >= $category->max_entrants) {
            throw RegistrationException::atCapacity();
        }
    }
}
