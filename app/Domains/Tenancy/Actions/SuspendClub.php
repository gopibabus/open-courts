<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Actions;

use App\Domains\Tenancy\Enums\ClubStatus;
use App\Domains\Tenancy\Events\ClubSuspended;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Suspend a club: freeze its workspace so its subdomain can no longer be entered
 * (enforced by App\Http\Middleware\EnsureClubActive). Idempotent — suspending an
 * already-suspended club is a no-op and does NOT re-emit the event.
 */
final class SuspendClub
{
    public function handle(Tenant $club): Tenant
    {
        if ($club->isSuspended()) {
            return $club;
        }

        DB::transaction(function () use ($club): void {
            $club->status = ClubStatus::Suspended;
            $club->save();

            ClubSuspended::dispatch($club);
        });

        return $club;
    }
}
