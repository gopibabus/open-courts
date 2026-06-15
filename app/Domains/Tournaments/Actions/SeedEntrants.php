<?php

declare(strict_types=1);

namespace App\Domains\Tournaments\Actions;

use App\Domains\Tournaments\Models\Registration;
use App\Domains\Tournaments\Models\TournamentCategory;
use Illuminate\Support\Facades\DB;

/**
 * Persist a manual seeding for a category: each registration's `seed` is set to its position
 * in the given order (1-based). Generation then respects seed order. Tenant-scoped via the
 * category_id constraint.
 */
final class SeedEntrants
{
    /**
     * @param  array<int, int>  $orderedRegistrationIds
     */
    public function handle(TournamentCategory $category, array $orderedRegistrationIds): void
    {
        DB::transaction(function () use ($category, $orderedRegistrationIds): void {
            foreach (array_values($orderedRegistrationIds) as $index => $registrationId) {
                Registration::where('category_id', $category->id)
                    ->whereKey($registrationId)
                    ->update(['seed' => $index + 1]);
            }
        });
    }
}
