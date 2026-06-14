<?php

declare(strict_types=1);

namespace App\Domains\Support\Models;

use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * A help-desk request raised by a club member. Tenant-scoped via BelongsToTenant, so
 * every query (and route-model binding) is automatically limited to the current club.
 */
class SupportRequest extends Model
{
    use BelongsToTenant;

    /** tenant_id is auto-populated by the BelongsToTenant trait, so it is omitted here. */
    protected $fillable = [
        'user_id',
        'category',
        'subject',
        'message',
        'status',
    ];

    /** The member who raised the request. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
