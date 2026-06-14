<?php

declare(strict_types=1);

namespace App\Domains\Membership\Events;

use App\Domains\Identity\Models\User;
use App\Domains\Membership\Models\Invitation;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An invitee accepted their invitation and joined the club. Dispatched AFTER the writing
 * transaction commits (ShouldDispatchAfterCommit). No listener is required yet.
 */
final class InvitationAccepted implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Invitation $invitation,
        public readonly User $user,
        public readonly Tenant $club,
    ) {}
}
