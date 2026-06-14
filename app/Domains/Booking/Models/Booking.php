<?php

declare(strict_types=1);

namespace App\Domains\Booking\Models;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Facilities\Models\Court;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * A member's reservation of a court for a time window. Tenant-scoped (row-level)
 * via the BelongsToTenant trait. Conflict-freedom (no two reserved bookings overlap
 * on the same court) is enforced in App\Domains\Booking\Actions\BookCourt under a
 * row-locking transaction — never trust this model to be conflict-free on its own.
 *
 * Money is stored as integer minor units (price_cents) + an ISO currency code, per
 * the repo's money convention.
 */
class Booking extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'court_id',
        'user_id',
        'starts_at',
        'ends_at',
        'status',
        'price_cents',
        'currency',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'status' => BookingStatus::class,
        'price_cents' => 'integer',
    ];

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Only reserved bookings hold a court — the set the overlap check runs against. */
    public function scopeReserved(Builder $query): Builder
    {
        return $query->where('status', BookingStatus::Reserved);
    }
}
