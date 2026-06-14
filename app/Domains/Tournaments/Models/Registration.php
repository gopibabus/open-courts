<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Tournaments\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * An entrant's registration into a tournament category. Tenant-scoped.
 *
 * `partner` is the doubles/mixed partner (null for singles). `seed` reserves room for a
 * later draw-generation slice — it is not assigned here.
 */
class Registration extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tournament_id',
        'category_id',
        'user_id',
        'partner_id',
        'seed',
        'status',
    ];

    protected $casts = [
        'status' => RegistrationStatus::class,
        'seed' => 'integer',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TournamentCategory::class, 'category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
    }
}
