<?php

declare(strict_types=1);

namespace App\Domains\Membership\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Membership\Events\RoleAssigned;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Set a member's role within a club. Roles are club-scoped (spatie teams, team = tenant),
 * so we pin the permissions team to the club before syncing. `syncRoles` makes the member
 * hold exactly one role in this club (replacing any prior club role).
 *
 * Emits RoleAssigned after commit.
 */
final class AssignMemberRole
{
    public function handle(Tenant $club, User $member, string $role): void
    {
        DB::transaction(function () use ($club, $member, $role): void {
            $registrar = app(PermissionRegistrar::class);
            $registrar->setPermissionsTeamId($club->getTenantKey());

            // Drop any cached roles so the team switch above takes effect.
            $member->unsetRelation('roles');
            $member->syncRoles([$role]);

            RoleAssigned::dispatch($club, $member, $role);
        });
    }
}
