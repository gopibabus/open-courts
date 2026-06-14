<?php

declare(strict_types=1);

namespace App\Domains\Support\Events;

use App\Domains\Support\Models\SupportRequest;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A club member submitted a help-desk request. Dispatched AFTER the writing transaction
 * commits (ShouldDispatchAfterCommit) so listeners never notify support about a request
 * that was rolled back.
 */
final class SupportRequestSubmitted implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SupportRequest $supportRequest,
    ) {}
}
