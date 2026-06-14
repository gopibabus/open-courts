<?php

declare(strict_types=1);

namespace App\Domains\Membership\Actions;

use App\Domains\Membership\Events\MemberInvited;
use App\Domains\Membership\Models\Invitation;
use Illuminate\Support\Facades\DB;

/**
 * Invite someone to the current club by email + role.
 *
 * Idempotent on the pending invite: if a still-actionable invitation already exists for
 * this (club, email), it is refreshed (role/token/expiry reset) rather than duplicated —
 * this also dodges the (tenant_id, email) unique constraint. The MemberInvited event fires
 * after commit so the invite email is (re)sent.
 *
 * Runs inside the active tenant context, so BelongsToTenant stamps `tenant_id`.
 */
final class InviteMember
{
    public function handle(string $email, string $role, ?int $invitedBy = null): Invitation
    {
        return DB::transaction(function () use ($email, $role, $invitedBy): Invitation {
            $email = mb_strtolower(trim($email));

            $invitation = Invitation::query()->firstOrNew(['email' => $email]);

            $invitation->fill([
                'role' => $role,
                'token' => Invitation::generateToken(),
                'invited_by' => $invitedBy,
                'expires_at' => Invitation::defaultExpiry(),
                'accepted_at' => null,
            ]);
            $invitation->save();

            MemberInvited::dispatch($invitation);

            return $invitation;
        });
    }
}
