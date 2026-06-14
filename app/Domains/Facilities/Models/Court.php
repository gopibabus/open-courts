<?php

declare(strict_types=1);

namespace App\Domains\Facilities\Models;

use App\Domains\Booking\Models\Booking;
use App\Domains\Facilities\Policies\CourtPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[UsePolicy(CourtPolicy::class)]
class Court extends Model
{
    use BelongsToTenant;

    /** tenant_id is auto-populated by the BelongsToTenant trait, so it is omitted here. */
    protected $fillable = [
        'name',
        'surface',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** Recurring weekly windows during which this court is open. */
    public function availability(): HasMany
    {
        return $this->hasMany(CourtAvailability::class);
    }

    /** One-off periods when this court is specifically blacked out. */
    public function blackouts(): HasMany
    {
        return $this->hasMany(CourtBlackout::class);
    }
}
