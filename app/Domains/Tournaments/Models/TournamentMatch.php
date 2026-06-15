<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Tournaments\Enums\MatchRound;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * A singles match within a tournament category. Tenant-scoped via BelongsToTenant.
 *
 * Doubles as a recorded ad-hoc result AND a node in a generated single-elimination bracket:
 * `position` places it in its round, and the winner advances into `nextMatch` slot
 * `next_slot`. Players + winner are nullable (a future-round match has TBD players);
 * `status` is `scheduled` until a result is recorded (`completed`). A player's competitive
 * record is derived from COMPLETED matches (those with a winner).
 */
class TournamentMatch extends Model
{
    use BelongsToTenant;

    protected $table = 'tournament_matches';

    /** tenant_id is auto-populated by the BelongsToTenant trait, so it is omitted here. */
    protected $fillable = [
        'tournament_id',
        'category_id',
        'round',
        'position',
        'next_match_id',
        'next_slot',
        'player_one_id',
        'player_two_id',
        'winner_id',
        'score',
        'notes',
        'status',
        'played_at',
    ];

    protected $casts = [
        'round' => MatchRound::class,
        'played_at' => 'datetime',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TournamentCategory::class, 'category_id');
    }

    public function playerOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_one_id');
    }

    public function playerTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_two_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    /** The bracket match this match's winner advances into (null for the final / ad-hoc results). */
    public function nextMatch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'next_match_id');
    }

    /** Images attached to this match. */
    public function attachments(): HasMany
    {
        return $this->hasMany(MatchAttachment::class, 'match_id');
    }

    /** The loser of the match (the player who is not the winner), or null if not decided. */
    public function loserId(): ?int
    {
        if ($this->winner_id === null) {
            return null;
        }

        return $this->winner_id === $this->player_one_id ? $this->player_two_id : $this->player_one_id;
    }
}
