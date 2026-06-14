<?php

declare(strict_types=1);

namespace App\Domains\Membership\Listeners;

use App\Domains\Membership\Events\MemberInvited;
use App\Domains\Membership\Mail\InvitationMail;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Queued, idempotent side effect of MemberInvited: email the invitee their accept link.
 *
 * The club (tenant) is looked up from the invitation's tenant_id rather than the active
 * tenant context, since a queued job may run with no tenant initialised.
 */
final class SendInvitationEmail implements ShouldQueue
{
    public function handle(MemberInvited $event): void
    {
        $invitation = $event->invitation;
        $club = Tenant::query()->findOrFail($invitation->tenant_id);

        Mail::to($invitation->email)->send(new InvitationMail($invitation, $club));
    }
}
