<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Exceptions;

use RuntimeException;

/**
 * Thrown when an entrant registration is rejected by domain rules: the registration
 * window is closed, the category is at capacity, or the entrant is already registered.
 *
 * Controllers translate this into a 422 validation error (see RegistrationController).
 */
final class RegistrationException extends RuntimeException
{
    public static function windowClosed(): self
    {
        return new self('Registration for this category is not currently open.');
    }

    public static function atCapacity(): self
    {
        return new self('This category is full.');
    }

    public static function duplicate(): self
    {
        return new self('You are already registered for this category.');
    }
}
