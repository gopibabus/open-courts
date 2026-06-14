<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Tournaments\Models\Tournament;

/**
 * Authorization for tournament management. Creating/managing tournaments & categories
 * and opening registration all require the club-scoped `tournament.manage` permission.
 *
 * The tenant routes are already guarded with `can:tournament.manage`, so this policy is
 * kept for completeness / explicit `$user->can()` checks. Registering an entrant only
 * requires being an authenticated club member (no policy check — see RegistrationController).
 */
class TournamentPolicy
{
    /**
     * View any tournament — any authenticated club member may browse the list.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tournament $tournament): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('tournament.manage');
    }

    public function update(User $user, Tournament $tournament): bool
    {
        return $user->can('tournament.manage');
    }

    /**
     * Add a category / open registration — same management permission.
     */
    public function manage(User $user, Tournament $tournament): bool
    {
        return $user->can('tournament.manage');
    }
}
