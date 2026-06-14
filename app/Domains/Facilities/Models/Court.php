<?php

declare(strict_types=1);

namespace App\Domains\Facilities\Models;

use App\Domains\Booking\Models\Booking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

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
}
