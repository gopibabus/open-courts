<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * An image attached to a tournament match (scorecard photo, etc.). Tenant-scoped. The file
 * lives on the `public` disk; this row stores its path. Deleting the row does not remove the
 * file — the controller unlinks it explicitly.
 */
class MatchAttachment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'match_id',
        'uploaded_by',
        'path',
        'original_name',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'match_id');
    }

    /** Public URL for the stored file. */
    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
