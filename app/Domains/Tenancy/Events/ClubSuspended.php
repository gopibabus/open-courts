<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Events;

use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A club was suspended by a platform operator (its subdomain can no longer be entered).
 * Dispatched AFTER the writing transaction commits so listeners never act on a rollback.
 */
final class ClubSuspended implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Tenant $club,
    ) {}
}
