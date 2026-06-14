<?php

declare(strict_types=1);

namespace App\Domains\Notifications\Listeners;

use App\Domains\Notifications\Mail\SupportRequestMail;
use App\Domains\Support\Events\SupportRequestSubmitted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Queued, idempotent side effect of SupportRequestSubmitted: email the support inbox
 * (config/branding.php → support_email) so the team can pick up the member's request.
 */
final class SendSupportRequestNotification implements ShouldQueue
{
    public function handle(SupportRequestSubmitted $event): void
    {
        $inbox = config('branding.support_email');

        Mail::to($inbox)->send(new SupportRequestMail($event->supportRequest));
    }
}
