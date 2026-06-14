<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Events;

use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A previously-suspended club was reactivated by a platform operator (its subdomain can be
 * entered again). Dispatched AFTER the writing transaction commits.
 */
final class ClubReactivated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Tenant $club,
    ) {}
}
