<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Exceptions;

use RuntimeException;

/**
 * Thrown when a tournament-management (EC) change is rejected by domain rules: the person
 * is not a member of the club, or they are already on the management.
 *
 * Controllers translate this into a 422 validation error.
 */
final class ManagementException extends RuntimeException
{
    public static function notAMember(): self
    {
        return new self('That person is not a member of this club.');
    }

    public static function duplicate(): self
    {
        return new self('That person is already on this tournament’s management.');
    }
}
