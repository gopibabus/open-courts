<?php

declare(strict_types=1);

namespace App\Domains\Membership\Exceptions;

use RuntimeException;

/**
 * Thrown when an invitation can no longer be accepted (expired or already accepted).
 */
final class InvitationNotAcceptable extends RuntimeException
{
    public function __construct(string $message = 'This invitation is no longer valid.')
    {
        parent::__construct($message);
    }
}
