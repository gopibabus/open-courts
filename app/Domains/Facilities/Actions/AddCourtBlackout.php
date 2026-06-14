<?php

declare(strict_types=1);

namespace App\Domains\Facilities\Actions;

use App\Domains\Facilities\Data\BlackoutData;
use App\Domains\Facilities\Models\CourtBlackout;
use Illuminate\Support\Facades\DB;

/**
 * Add a one-off blackout. A null courtId blacks out the whole club. tenant_id is
 * stamped by BelongsToTenant.
 */
final class AddCourtBlackout
{
    public function handle(BlackoutData $data): CourtBlackout
    {
        return DB::transaction(function () use ($data): CourtBlackout {
            return CourtBlackout::create([
                'court_id' => $data->courtId,
                'starts_at' => $data->startsAt,
                'ends_at' => $data->endsAt,
                'reason' => $data->reason,
            ]);
        });
    }
}
