<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Tournaments\Enums\TournamentFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Tournament extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'starts_on',
        'ends_on',
        'status',
        'format',
        'registration_opens_on',
        'registration_closes_on',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'registration_opens_on' => 'date',
        'registration_closes_on' => 'date',
        'format' => TournamentFormat::class,
    ];

    /**
     * Tournament squads (the domain "team" concept — unrelated to spatie teams).
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * Categories (events) within this tournament — singles / doubles / mixed.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(TournamentCategory::class);
    }

    /**
     * All entrant registrations across this tournament's categories.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * The tournament's management — the EC (executive committee). Club members who run
     * THIS tournament; the set can differ from tournament to tournament. Pass tenant_id
     * when attaching (attach() bypasses model events).
     */
    public function management(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tournament_management')->withTimestamps();
    }
}
