<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Tournaments\Models\MatchAttachment;
use App\Domains\Tournaments\Models\TournamentMatch;
use Illuminate\Http\UploadedFile;

/**
 * Store an uploaded image on the `public` disk and record it against a match. tenant_id is
 * filled automatically by BelongsToTenant.
 */
final class UploadMatchImage
{
    public function handle(TournamentMatch $match, UploadedFile $file, ?int $uploadedBy): MatchAttachment
    {
        $path = $file->store('match-attachments', 'public');

        return MatchAttachment::create([
            'match_id' => $match->id,
            'uploaded_by' => $uploadedBy,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
        ]);
    }
}
