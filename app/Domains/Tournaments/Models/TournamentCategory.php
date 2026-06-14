<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Models;

use App\Domains\Tournaments\Enums\CategoryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * A category (event) within a tournament — e.g. "Men's Singles". Tenant-scoped.
 *
 * `max_entrants` caps how many entrants may register (null = unlimited). Draw/seeding
 * generation is OUT OF SCOPE for this slice.
 */
class TournamentCategory extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tournament_id',
        'name',
        'type',
        'max_entrants',
    ];

    protected $casts = [
        'type' => CategoryType::class,
        'max_entrants' => 'integer',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * Entrant registrations for this category.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'category_id');
    }
}
