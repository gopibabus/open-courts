<?php

declare(strict_types=1);

use App\Http\Controllers\Tournaments\CategoryController;
use App\Http\Controllers\Tournaments\RegistrationController;
use App\Http\Controllers\Tournaments\TournamentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tournament (club) Routes — Tournaments bounded context
|--------------------------------------------------------------------------
|
| Auto-loaded by routes/tenant.php inside the tenant + subdomain group (but NOT
| inside 'auth'), so we apply 'auth' here ourselves. Management actions (create a
| tournament, add a category, open registration) require the club-scoped
| `tournament.manage` permission; registering/withdrawing only requires being an
| authenticated club member.
|
| Route-model bindings ({tournament}, {category}, {registration}) resolve through
| BelongsToTenant, so they are automatically scoped to the current club.
|
| OUT OF SCOPE for this slice: draws, scheduling, scoring, standings — a later slice.
|
*/

Route::middleware('auth')->group(function () {
    // Read — any authenticated club member. `create` is declared before the
    // `{tournament}` wildcard so the literal segment wins route matching.
    Route::get('tournaments', [TournamentController::class, 'index'])->name('tournaments.index');

    Route::middleware('can:tournament.manage')->group(function () {
        Route::get('tournaments/create', [TournamentController::class, 'create'])->name('tournaments.create');
        Route::post('tournaments', [TournamentController::class, 'store'])->name('tournaments.store');
        Route::post('tournaments/{tournament}/categories', [CategoryController::class, 'store'])
            ->name('tournaments.categories.store');
        Route::post('tournaments/{tournament}/open-registration', [TournamentController::class, 'openRegistration'])
            ->name('tournaments.open-registration');
    });

    Route::get('tournaments/{tournament}', [TournamentController::class, 'show'])->name('tournaments.show');

    // Entrant self-service — any authenticated club member.
    Route::post('tournaments/categories/{category}/registrations', [RegistrationController::class, 'store'])
        ->name('tournaments.registrations.store');
    Route::delete('tournaments/registrations/{registration}', [RegistrationController::class, 'destroy'])
        ->name('tournaments.registrations.destroy');
});
