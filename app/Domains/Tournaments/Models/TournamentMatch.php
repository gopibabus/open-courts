<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Tournaments\Enums\MatchRound;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * A recorded singles match result within a tournament category. Tenant-scoped via
 * BelongsToTenant. Every row is a completed result: it always has a winner (one of the
 * two players). A player's competitive record + trophies are derived from these rows.
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
        'player_one_id',
        'player_two_id',
        'winner_id',
        'score',
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

    /** The loser of the match (the player who is not the winner). */
    public function loserId(): int
    {
        return $this->winner_id === $this->player_one_id ? $this->player_two_id : $this->player_one_id;
    }
}
