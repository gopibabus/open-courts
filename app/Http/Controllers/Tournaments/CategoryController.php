<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tournaments;

use App\Domains\Tournaments\Actions\AddCategory;
use App\Domains\Tournaments\Models\Tournament;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreCategoryRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Add categories (events) to a tournament. Guarded by `can:tournament.manage` at the
 * route layer. The {tournament} binding is tenant-scoped (BelongsToTenant), so a club
 * can only attach categories to its own tournaments.
 */
class CategoryController extends Controller
{
    public function store(StoreCategoryRequest $request, Tournament $tournament, AddCategory $addCategory): RedirectResponse
    {
        $addCategory->handle($tournament, $request->toData());

        return redirect()
            ->route('tournaments.show', $tournament)
            ->with('status', 'Category added.');
    }
}
