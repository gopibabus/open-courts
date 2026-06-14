<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Events;

use App\Domains\Tournaments\Models\Tournament;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Registration was opened for a tournament (window dates set, status -> 'open').
 * Dispatched AFTER commit. No listeners are registered in this slice.
 */
final class RegistrationOpened implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Tournament $tournament,
    ) {}
}
