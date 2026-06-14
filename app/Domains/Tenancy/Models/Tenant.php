<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Models;

use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * A Tenant is a tennis club / facility. Single-database (row-level) tenancy: the
 * HasDatabase trait only satisfies stancl's contract — no database is provisioned.
 *
 * `id`, `name`, `slug` are real columns (getCustomColumns); other attributes fold
 * into the `data` JSON column via stancl's VirtualColumn trait.
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
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
     * Members of this club (central pivot, not tenant-scoped).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withTimestamps();
    }
}
