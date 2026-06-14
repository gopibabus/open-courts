<?php

declare(strict_types=1);

namespace App\Domains\Facilities\Actions;

use App\Domains\Facilities\Data\AvailabilityWindowData;
use App\Domains\Facilities\Events\CourtAvailabilityChanged;
use App\Domains\Facilities\Models\Court;
use App\Domains\Facilities\Models\CourtAvailability;
use Illuminate\Support\Facades\DB;

/**
 * Replace a court's entire set of weekly availability windows in one shot
 * (delete-then-insert). tenant_id is stamped on each new row by BelongsToTenant.
 * Emits CourtAvailabilityChanged after commit.
 */
final class SetCourtAvailability
{
    /**
     * @param  list<AvailabilityWindowData>  $windows
     */
    public function handle(Court $court, array $windows): Court
    {
        return DB::transaction(function () use ($court, $windows): Court {
            $court->availability()->delete();

            foreach ($windows as $window) {
                CourtAvailability::create([
                    'court_id' => $court->id,
                    'day_of_week' => $window->dayOfWeek,
                    'opens_at' => $window->opensAt,
                    'closes_at' => $window->closesAt,
                ]);
            }

            CourtAvailabilityChanged::dispatch($court);

            return $court->load('availability');
        });
    }
}
