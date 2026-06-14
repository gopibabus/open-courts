<?php

declare(strict_types=1);

namespace App\Domains\Facilities\Events;

use App\Domains\Facilities\Models\Court;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A new court was added to a club. Dispatched AFTER the writing transaction commits
 * (ShouldDispatchAfterCommit) so listeners never act on a rollback.
 */
final class CourtAdded implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Court $court,
    ) {}
}
