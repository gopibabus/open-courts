<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Models;

use App\Domains\Identity\Models\User;
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
     * Players on this team. Pass tenant_id when attaching (attach() bypasses model events).
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_player')
            ->withTimestamps();
    }
}
