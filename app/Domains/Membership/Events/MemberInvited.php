<?php

declare(strict_types=1);

namespace App\Domains\Membership\Events;

use App\Domains\Membership\Models\Invitation;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Someone was invited to join a club. Dispatched AFTER the writing transaction commits
 * (ShouldDispatchAfterCommit) so the queued SendInvitationEmail listener never mails on a
 * rollback.
 */
final class MemberInvited implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Invitation $invitation,
    ) {}
}
