<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Events;

use App\Domains\Tournaments\Models\ClubWaiverTemplate;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A club's waiver template was created or edited. Dispatched after commit; the seam for a
 * future "notify entrants their waiver changed / require re-signing" listener.
 */
final class WaiverTemplateUpdated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ClubWaiverTemplate $template,
    ) {}
}
