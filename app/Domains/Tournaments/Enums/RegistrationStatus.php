<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Enums;

/**
 * Lifecycle of an entrant's registration. String-backed PHP enum stored in the
 * `registrations.status` column (DB-neutral — no DB enum, per ADR-0001).
 */
enum RegistrationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Withdrawn = 'withdrawn';
}
