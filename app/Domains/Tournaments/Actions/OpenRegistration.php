<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Tournaments\Data\OpenRegistrationData;
use App\Domains\Tournaments\Events\RegistrationOpened;
use App\Domains\Tournaments\Models\Tournament;
use Illuminate\Support\Facades\DB;

/**
 * Open registration for a tournament: persist the registration window and flip the
 * status to 'open'. The RegistrationOpened event fires after commit.
 */
final class OpenRegistration
{
    public function handle(Tournament $tournament, OpenRegistrationData $data): Tournament
    {
        return DB::transaction(function () use ($tournament, $data): Tournament {
            $tournament->update([
                'status' => 'open',
                'registration_opens_on' => $data->opensOn,
                'registration_closes_on' => $data->closesOn,
            ]);

            RegistrationOpened::dispatch($tournament);

            return $tournament;
        });
    }
}
