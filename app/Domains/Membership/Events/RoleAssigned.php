<?php

declare(strict_types=1);

namespace App\Domains\Membership\Events;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A member's role within a club was (re)assigned. Dispatched AFTER the writing transaction
 * commits (ShouldDispatchAfterCommit). No listener is required yet — projections/audit may
 * subscribe later.
 */
final class RoleAssigned implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Tenant $club,
        public readonly User $member,
        public readonly string $role,
    ) {}
}
