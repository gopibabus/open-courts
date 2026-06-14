<?php

declare(strict_types=1);

namespace App\Domains\Facilities\Policies;

use App\Domains\Facilities\Models\Court;
use App\Domains\Identity\Models\User;

/**
 * Authorisation for court management. VIEWING courts is open to any authenticated
 * club member, so there is no view ability here — only the mutating abilities, all
 * of which require the club-scoped `court.manage` permission.
 *
 * Note: the platform super-admin bypasses every ability via the `Gate::before`
 * hook in AppServiceProvider, so it is not handled here.
 */
class CourtPolicy
{
    public function create(User $user): bool
    {
        return $user->can('court.manage');
    }

    public function update(User $user, Court $court): bool
    {
        return $user->can('court.manage');
    }

    public function delete(User $user, Court $court): bool
    {
        return $user->can('court.manage');
    }
}
