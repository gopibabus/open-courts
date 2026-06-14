<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Events;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A new club was provisioned (tenant + owner + roles). Dispatched AFTER the writing
 * transaction commits (ShouldDispatchAfterCommit) so listeners never act on a rollback.
 */
final class ClubRegistered implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Tenant $club,
        public readonly User $owner,
    ) {}
}
