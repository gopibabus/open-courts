<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Enums;

/**
 * Lifecycle status of a club (tenant). Backed by the `tenants.status` string column.
 *
 * - `Active`: the club is operational and its subdomain can be entered.
 * - `Suspended`: the club is frozen by a platform operator; entering its subdomain
 *   is blocked by App\Http\Middleware\EnsureClubActive (403). The data is retained
 *   and the club can be reactivated.
 */
enum ClubStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';

    /** Human-readable label for UI. */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspended',
        };
    }
}
