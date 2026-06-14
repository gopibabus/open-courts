<?php

declare(strict_types=1);

namespace App\Domains\Facilities\Actions;

use App\Domains\Facilities\Data\CourtData;
use App\Domains\Facilities\Models\Court;
use Illuminate\Support\Facades\DB;

/**
 * Update an existing court's details. The court is already tenant-scoped (it can
 * only be resolved within the current club), so no extra ownership check is needed.
 */
final class UpdateCourt
{
    public function handle(Court $court, CourtData $data): Court
    {
        return DB::transaction(function () use ($court, $data): Court {
            $court->update([
                'name' => $data->name,
                'surface' => $data->surface,
                'is_active' => $data->isActive,
            ]);

            return $court;
        });
    }
}
