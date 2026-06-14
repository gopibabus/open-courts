<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Team extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tournament_id',
        'name',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * Players on this team. Remember to pass tenant_id when attaching (see the
     * team_player migration note) since attach() bypasses model events.
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_player')
            ->withTimestamps();
    }
}
