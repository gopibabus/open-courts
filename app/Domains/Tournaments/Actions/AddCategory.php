<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Tournaments\Data\AddCategoryData;
use App\Domains\Tournaments\Models\Tournament;
use App\Domains\Tournaments\Models\TournamentCategory;
use Illuminate\Support\Facades\DB;

/**
 * Add a category (event) to a tournament. The category inherits the tournament's club
 * via BelongsToTenant. No event is emitted for this slice.
 */
final class AddCategory
{
    public function handle(Tournament $tournament, AddCategoryData $data): TournamentCategory
    {
        return DB::transaction(function () use ($tournament, $data): TournamentCategory {
            return $tournament->categories()->create([
                'name' => $data->name,
                'type' => $data->type,
                'format' => $data->format,
                'is_team' => $data->isTeam,
                'max_entrants' => $data->maxEntrants,
            ]);
        });
    }
}
