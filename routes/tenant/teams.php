<?php

declare(strict_types=1);

use App\Http\Controllers\Tournaments\RosterController;
use App\Http\Controllers\Tournaments\TeamController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Team & Roster (club) Routes — Tournaments bounded context
|--------------------------------------------------------------------------
|
| Auto-loaded by routes/tenant.php inside the tenant + subdomain group (but NOT
| inside 'auth'), so we apply 'auth' here ourselves. Any authenticated club member
| may VIEW teams (index/show); creating/deleting teams and managing rosters require
| the club-scoped `team.manage` permission (granted to club-admin and coach).
|
| The {team} binding resolves through BelongsToTenant, so it is automatically scoped
| to the current club. The {player} binding is a User (no tenant scope); the
| club-membership rule is enforced in AddPlayerToTeam (RosterException -> 422).
|
| OUT OF SCOPE for this slice: linking a team to specific tournament categories /
| registrations — a later slice. `teams.tournament_id` already allows the optional link.
|
*/

Route::middleware('auth')->group(function () {
    // Read — any authenticated club member. `create`-style segments are not used; the
    // create form lives in a dialog on the index page.
    Route::get('teams', [TeamController::class, 'index'])->name('teams.index');
    Route::get('teams/{team}', [TeamController::class, 'show'])->name('teams.show');

    // Write — requires the club-scoped `team.manage` permission.
    Route::middleware('can:team.manage')->group(function () {
        Route::post('teams', [TeamController::class, 'store'])->name('teams.store');
        Route::delete('teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');

        // Roster management.
        Route::post('teams/{team}/players', [RosterController::class, 'store'])
            ->name('teams.players.store');
        Route::delete('teams/{team}/players/{player}', [RosterController::class, 'destroy'])
            ->name('teams.players.destroy');
    });
});
