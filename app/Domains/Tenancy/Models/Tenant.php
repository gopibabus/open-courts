<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Enums\ClubStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * A Tenant is a tennis club / facility. Single-database (row-level) tenancy: the
 * HasDatabase trait only satisfies stancl's contract — no database is provisioned.
 *
 * `id`, `name`, `slug`, `status` are real columns (getCustomColumns); other attributes
 * fold into the `data` JSON column via stancl's VirtualColumn trait.
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * Real columns. `status` MUST be listed here, otherwise stancl's VirtualColumn
     * trait would fold it into the `data` JSON blob instead of the real column.
     *
     * @return array<int, string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'status',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            'status' => ClubStatus::class,
        ]);
    }

    /**
     * Members of this club (central pivot, not tenant-scoped).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withTimestamps();
    }

    /** Whether the club is operational (its subdomain may be entered). */
    public function isActive(): bool
    {
        return $this->status === ClubStatus::Active;
    }

    /** Whether the club is frozen by a platform operator. */
    public function isSuspended(): bool
    {
        return $this->status === ClubStatus::Suspended;
    }

    /**
     * Only active clubs.
     *
     * @param  Builder<Tenant>  $query
     * @return Builder<Tenant>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ClubStatus::Active->value);
    }

    /**
     * Only suspended clubs.
     *
     * @param  Builder<Tenant>  $query
     * @return Builder<Tenant>
     */
    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('status', ClubStatus::Suspended->value);
    }
}
