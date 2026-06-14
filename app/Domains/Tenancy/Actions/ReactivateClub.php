<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Actions;

use App\Domains\Tenancy\Enums\ClubStatus;
use App\Domains\Tenancy\Events\ClubReactivated;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Reactivate a previously-suspended club: restore access to its subdomain. Idempotent —
 * reactivating an already-active club is a no-op and does NOT re-emit the event.
 */
final class ReactivateClub
{
    public function handle(Tenant $club): Tenant
    {
        if ($club->isActive()) {
            return $club;
        }

        DB::transaction(function () use ($club): void {
            $club->status = ClubStatus::Active;
            $club->save();

            ClubReactivated::dispatch($club);
        });

        return $club;
    }
}
