<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Events;

use App\Domains\Tournaments\Models\TournamentWaiver;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A player signed (or re-signed) their waiver for a tournament. Dispatched after commit;
 * the seam for a future "email a copy / notify the organisers" listener.
 */
final class WaiverSigned implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly TournamentWaiver $waiver,
    ) {}
}
