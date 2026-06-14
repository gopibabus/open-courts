<?php

declare(strict_types=1);

namespace App\Domains\Facilities\Actions;

use App\Domains\Facilities\Data\CourtData;
use App\Domains\Facilities\Events\CourtAdded;
use App\Domains\Facilities\Models\Court;
use Illuminate\Support\Facades\DB;

/**
 * Add a court to the current club. tenant_id is stamped automatically by the
 * BelongsToTenant trait. Emits CourtAdded after commit.
 */
final class CreateCourt
{
    public function handle(CourtData $data): Court
    {
        return DB::transaction(function () use ($data): Court {
            $court = Court::create([
                'name' => $data->name,
                'surface' => $data->surface,
                'is_active' => $data->isActive,
            ]);

            CourtAdded::dispatch($court);

            return $court;
        });
    }
}
