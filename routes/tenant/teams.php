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
| A team belongs to a tournament (created via tournaments.teams.store) and a club member
| may be on only one team per tournament. The {team} binding is tenant-scoped.
|
*/

Route::middleware('auth')->group(function () {
    // Read — any authenticated club member. Teams live under a tournament, so there is no
    // standalone teams index; a team's roster page is reached from its tournament.
    Route::get('teams/{team}', [TeamController::class, 'show'])->name('teams.show');

    // Write — requires the club-scoped `team.manage` permission. Team creation lives under
    // a tournament (tournaments.teams.store); here we delete teams + manage rosters.
    Route::middleware('can:team.manage')->group(function () {
        Route::delete('teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');

        // Roster management.
        Route::post('teams/{team}/players', [RosterController::class, 'store'])
            ->name('teams.players.store');
        Route::delete('teams/{team}/players/{player}', [RosterController::class, 'destroy'])
            ->name('teams.players.destroy');
    });
});
