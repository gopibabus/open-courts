<?php

declare(strict_types=1);

namespace App\Domains\Facilities\Events;

use App\Domains\Facilities\Models\Court;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A court's recurring weekly availability windows were replaced. Dispatched AFTER the
 * writing transaction commits (ShouldDispatchAfterCommit) so listeners never act on a
 * rollback. Downstream (e.g. booking) can recompute bookable slots when needed.
 */
final class CourtAvailabilityChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Court $court,
    ) {}
}
