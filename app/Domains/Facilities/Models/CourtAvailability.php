<?php

declare(strict_types=1);

namespace App\Domains\Facilities\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * A recurring weekly window during which a court is open. `day_of_week` is
 * 0=Mon..6=Sun. Tenant-scoped (row-level) via the BelongsToTenant trait.
 */
class CourtAvailability extends Model
{
    use BelongsToTenant;

    /** Non-default table name (singular "availability" reads better than a pluralised guess). */
    protected $table = 'court_availability';

    /** tenant_id is auto-populated by the BelongsToTenant trait, so it is omitted here. */
    protected $fillable = [
        'court_id',
        'day_of_week',
        'opens_at',
        'closes_at',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'opens_at' => 'datetime:H:i',
        'closes_at' => 'datetime:H:i',
    ];

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
