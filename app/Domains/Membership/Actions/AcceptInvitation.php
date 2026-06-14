<?php

declare(strict_types=1);

namespace App\Domains\Membership\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Membership\Events\InvitationAccepted;
use App\Domains\Membership\Exceptions\InvitationNotAcceptable;
use App\Domains\Membership\Models\Invitation;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * Accept a pending invitation and join the invitee to the club.
 *
 * If the invited email has no account, a new user is created from {name, password};
 * otherwise the existing account is used (and {name, password} are ignored). The user is
 * attached to the club, granted the invitation's role (scoped to the club's team), and the
 * invitation is marked accepted. InvitationAccepted fires after commit.
 *
 * Must run inside the active tenant context (the invitation is tenant-scoped).
 *
 * @throws InvitationNotAcceptable when the invitation is expired or already accepted
 */
final class AcceptInvitation
{
    public function handle(Invitation $invitation, ?string $name = null, ?string $password = null): User
    {
        if (! $invitation->isPending()) {
            throw new InvitationNotAcceptable;
        }

        return DB::transaction(function () use ($invitation, $name, $password): User {
            $club = tenant();

            if (! $club instanceof Tenant) {
                // Fall back to the invitation's own tenant if called outside a request.
                $club = Tenant::query()->findOrFail($invitation->tenant_id);
            }

            $user = User::query()->where('email', $invitation->email)->first();

            if ($user === null) {
                $user = User::create([
                    'name' => $name ?: $invitation->email,
                    'email' => $invitation->email,
                    'password' => Hash::make((string) $password),
                ]);
            }

            // Idempotent club membership (central pivot, not tenant-scoped).
            $club->users()->syncWithoutDetaching([$user->id]);

            // Grant the invited role within this club's team context.
            app(PermissionRegistrar::class)->setPermissionsTeamId($club->getTenantKey());
            $user->unsetRelation('roles');
            $user->assignRole($invitation->role);

            $invitation->forceFill(['accepted_at' => now()])->save();

            InvitationAccepted::dispatch($invitation, $user, $club);

            return $user;
        });
    }
}
