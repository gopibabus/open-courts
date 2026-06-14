<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Tournaments\Events\ManagerAddedToTournament;
use App\Domains\Tournaments\Exceptions\ManagementException;
use App\Domains\Tournaments\Models\Tournament;
use Illuminate\Support\Facades\DB;

/**
 * Add a club member to a tournament's management (the EC). Enforces two domain rules
 * before persisting (throwing ManagementException on failure):
 *
 *   1. The person must be a member of this club.
 *   2. No duplicate — they are not already on this tournament's management.
 *
 * attach() bypasses model events, so the pivot tenant_id is stamped by hand. The
 * ManagerAddedToTournament event fires after commit.
 */
final class AddManagerToTournament
{
    public function handle(Tournament $tournament, User $user): User
    {
        return DB::transaction(function () use ($tournament, $user): User {
            $this->assertIsClubMember($tournament, $user);
            $this->assertNotAlready($tournament, $user);

            $tournament->management()->attach($user->getKey(), ['tenant_id' => $tournament->tenant_id]);

            ManagerAddedToTournament::dispatch($tournament, $user);

            return $user;
        });
    }

    private function assertIsClubMember(Tournament $tournament, User $user): void
    {
        $isMember = $tournament->tenant()
            ->firstOrFail()
            ->users()
            ->whereKey($user->getKey())
            ->exists();

        if (! $isMember) {
            throw ManagementException::notAMember();
        }
    }

    private function assertNotAlready(Tournament $tournament, User $user): void
    {
        if ($tournament->management()->whereKey($user->getKey())->exists()) {
            throw ManagementException::duplicate();
        }
    }
}
