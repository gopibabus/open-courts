<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Tournaments\Data\SignWaiverData;
use App\Domains\Tournaments\Events\WaiverSigned;
use App\Domains\Tournaments\Models\TournamentWaiver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Record a player's signed waiver for a tournament (idempotent — re-signing updates the
 * signature + timestamp). tenant_id is filled by BelongsToTenant. WaiverSigned fires after
 * commit.
 */
final class SignWaiver
{
    public function handle(SignWaiverData $data): TournamentWaiver
    {
        return DB::transaction(function () use ($data): TournamentWaiver {
            $waiver = TournamentWaiver::updateOrCreate(
                ['tournament_id' => $data->tournamentId, 'user_id' => $data->userId],
                ['signature' => $data->signature, 'signed_at' => Carbon::now()],
            );

            WaiverSigned::dispatch($waiver);

            return $waiver;
        });
    }
}
