<?php

declare(strict_types=1);

namespace App\Domains\Facilities\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * A one-off period when a court (or the whole club, when court_id is null) is
 * unavailable. Tenant-scoped (row-level) via the BelongsToTenant trait.
 */
class CourtBlackout extends Model
{
    use BelongsToTenant;

    /** tenant_id is auto-populated by the BelongsToTenant trait, so it is omitted here. */
    protected $fillable = [
        'court_id',
        'starts_at',
        'ends_at',
        'reason',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /** A null court means the blackout applies to the whole club. */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
