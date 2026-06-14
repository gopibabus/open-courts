<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * A Tenant is a tennis club / facility.
 *
 * We run in single-database (row-level) mode, so the HasDatabase trait is only
 * here to satisfy stancl's contract — no separate database is ever provisioned
 * (the CreateDatabase/MigrateDatabase jobs are disabled in TenancyServiceProvider).
 *
 * `id`, `name`, and `slug` are stored as real columns (see getCustomColumns);
 * any other attribute you set on a tenant is transparently persisted into the
 * `data` JSON column by stancl's VirtualColumn trait.
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * Columns stored as real DB columns rather than folded into the `data` JSON blob.
     *
     * @return array<int, string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
        ];
    }

    /**
     * Members of this club. The pivot is the central `tenant_user` table; a user
     * may belong to several clubs, and their role within each club is scoped by
     * spatie's teams feature (team_id === tenant_id).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withTimestamps();
    }
}
