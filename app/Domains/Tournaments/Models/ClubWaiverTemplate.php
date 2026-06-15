<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Models;

use App\Domains\Tournaments\Support\DefaultWaiver;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * A club's editable waiver template — the ordered clauses every player agrees to before
 * competing. Tenant-scoped, at most one row per club; clubs without a row use the platform
 * defaults. Clauses are raw templates that may contain the {tournament} placeholder.
 */
class ClubWaiverTemplate extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'clauses',
    ];

    protected $casts = [
        'clauses' => 'array',
    ];

    /**
     * The current club's template row, or null if it has never been customised. Relies on the
     * BelongsToTenant global scope, so it must run inside an initialised tenancy context.
     */
    public static function current(): ?self
    {
        return static::query()->first();
    }

    /**
     * The current club's effective raw clauses — its custom template if set, else the platform
     * defaults. Raw means the {tournament} placeholder is left intact (resolve at display time).
     *
     * @return array<int, string>
     */
    public static function clausesForClub(): array
    {
        return static::current()?->clauses ?? DefaultWaiver::clauses();
    }
}
