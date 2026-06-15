<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Models;

use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * A player's signed liability waiver for a tournament. Tenant-scoped. One per
 * (tournament, player) — see the unique index.
 */
class TournamentWaiver extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tournament_id',
        'user_id',
        'signature',
        'signed_clauses',
        'signed_at',
    ];

    protected $casts = [
        'signed_clauses' => 'array',
        'signed_at' => 'datetime',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
