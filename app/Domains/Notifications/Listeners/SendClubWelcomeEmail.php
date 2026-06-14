<?php

declare(strict_types=1);

namespace App\Domains\Notifications\Listeners;

use App\Domains\Notifications\Mail\ClubWelcomeMail;
use App\Domains\Tenancy\Events\ClubRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Queued, idempotent side effect of ClubRegistered: email the owner a welcome.
 */
final class SendClubWelcomeEmail implements ShouldQueue
{
    public function handle(ClubRegistered $event): void
    {
        Mail::to($event->owner->email)->send(new ClubWelcomeMail($event->club));
    }
}
