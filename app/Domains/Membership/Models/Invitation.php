<?php

declare(strict_types=1);

namespace App\Domains\Membership\Models;

use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * An invitation for someone to join a club (tenant) with an assigned role.
 *
 * Tenant-scoped (row-level) via `tenant_id`, which the BelongsToTenant trait
 * auto-populates from the active tenant. Looked up from the accept link by `token`.
 *
 * @property string $tenant_id
 * @property string $email
 * @property string $role
 * @property string $token
 * @property int|null $invited_by
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 */
class Invitation extends Model
{
    use BelongsToTenant;

    /** tenant_id is auto-populated by the BelongsToTenant trait, so it is omitted here. */
    protected $fillable = [
        'email',
        'role',
        'token',
        'invited_by',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    /**
     * The club admin who sent the invitation (nullable — they may have been removed).
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Generate a random, unguessable invitation token.
     */
    public static function generateToken(): string
    {
        return Str::random(40);
    }

    /**
     * Whether the invitation's expiry window has passed.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Whether the invitation is still actionable: not yet accepted and not expired.
     */
    public function isPending(): bool
    {
        return $this->accepted_at === null && ! $this->isExpired();
    }

    /**
     * Default expiry window for new invitations.
     */
    public static function defaultExpiry(): Carbon
    {
        return Carbon::now()->addDays(7);
    }
}
