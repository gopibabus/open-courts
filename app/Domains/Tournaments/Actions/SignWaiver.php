<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Tournaments\Data\SignWaiverData;
use App\Domains\Tournaments\Events\WaiverSigned;
use App\Domains\Tournaments\Models\ClubWaiverTemplate;
use App\Domains\Tournaments\Models\Tournament;
use App\Domains\Tournaments\Models\TournamentWaiver;
use App\Domains\Tournaments\Support\DefaultWaiver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Record a player's signed waiver for a tournament (idempotent — re-signing updates the
 * signature + timestamp + clause snapshot). The exact clauses agreed to are snapshotted onto
 * the row, so a later edit to the club template never changes what this player signed.
 * tenant_id is filled by BelongsToTenant. WaiverSigned fires after commit.
 */
final class SignWaiver
{
    public function handle(SignWaiverData $data): TournamentWaiver
    {
        return DB::transaction(function () use ($data): TournamentWaiver {
            $tournament = Tournament::findOrFail($data->tournamentId);
            $signedClauses = DefaultWaiver::resolve(
                ClubWaiverTemplate::clausesForClub(),
                $tournament->name,
            );

            $waiver = TournamentWaiver::updateOrCreate(
                ['tournament_id' => $data->tournamentId, 'user_id' => $data->userId],
                [
                    'signature' => $data->signature,
                    'signed_clauses' => $signedClauses,
                    'signed_at' => Carbon::now(),
                ],
            );

            WaiverSigned::dispatch($waiver);

            return $waiver;
        });
    }
}
