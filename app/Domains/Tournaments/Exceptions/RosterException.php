<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Exceptions;

use RuntimeException;

/**
 * Thrown when a roster change is rejected by domain rules: the user is not a member of
 * the club, or they are already on the team.
 *
 * Controllers translate this into a 422 validation error (see RosterController).
 */
final class RosterException extends RuntimeException
{
    public static function notAMember(): self
    {
        return new self('That person is not a member of this club.');
    }

    public static function duplicate(): self
    {
        return new self('That player is already on this team.');
    }

    public static function alreadyInTournament(): self
    {
        return new self('That player already plays for another team in this tournament.');
    }
}
