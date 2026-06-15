<?php

declare(strict_types=1);

use App\Http\Controllers\Tournaments\BracketController;
use App\Http\Controllers\Tournaments\CategoryController;
use App\Http\Controllers\Tournaments\ManagementController;
use App\Http\Controllers\Tournaments\MatchController;
use App\Http\Controllers\Tournaments\RegistrationController;
use App\Http\Controllers\Tournaments\TeamController;
use App\Http\Controllers\Tournaments\TournamentController;
use App\Http\Controllers\Tournaments\WaiverController;
use App\Http\Controllers\Tournaments\WaiverTemplateController;
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

        // The club's editable waiver template (the clauses every player signs). Declared as a
        // literal `tournaments/waiver-template` before the `tournaments/{tournament}` wildcard
        // (registered later in this file) so the literal segment wins route matching.
        Route::get('tournaments/waiver-template', [WaiverTemplateController::class, 'edit'])
            ->name('tournaments.waiver-template.edit');
        Route::put('tournaments/waiver-template', [WaiverTemplateController::class, 'update'])
            ->name('tournaments.waiver-template.update');
        Route::post('tournaments', [TournamentController::class, 'store'])->name('tournaments.store');
        Route::post('tournaments/{tournament}/categories', [CategoryController::class, 'store'])
            ->name('tournaments.categories.store');
        Route::post('tournaments/{tournament}/open-registration', [TournamentController::class, 'openRegistration'])
            ->name('tournaments.open-registration');

        // Tournament management (the EC) — add/remove the club members who run this
        // tournament. The set can differ from tournament to tournament.
        Route::post('tournaments/{tournament}/management', [ManagementController::class, 'store'])
            ->name('tournaments.management.store');
        Route::delete('tournaments/{tournament}/management/{user}', [ManagementController::class, 'destroy'])
            ->name('tournaments.management.destroy');

        // Match results — record who beat whom (drives players' competitive records + trophies).
        Route::post('tournaments/{tournament}/matches', [MatchController::class, 'store'])
            ->name('tournaments.matches.store');
        Route::delete('matches/{match}', [MatchController::class, 'destroy'])
            ->name('tournaments.matches.destroy');

        // Bracket — generate the single-elimination draw, then record results + attach images
        // on each match (recording a winner advances them to the next round).
        Route::post('categories/{category}/bracket', [BracketController::class, 'generate'])
            ->name('tournaments.bracket.generate');
        Route::patch('categories/{category}/seeding', [BracketController::class, 'seed'])
            ->name('tournaments.seeding.update');
        Route::patch('matches/{match}', [MatchController::class, 'update'])
            ->name('tournaments.matches.update');
        Route::post('matches/{match}/attachments', [MatchController::class, 'storeAttachment'])
            ->name('tournaments.matches.attachments.store');
        Route::delete('attachments/{attachment}', [MatchController::class, 'destroyAttachment'])
            ->name('tournaments.matches.attachments.destroy');
    });

    // Create a team within a tournament — requires the club-scoped `team.manage` permission.
    Route::middleware('can:team.manage')->group(function () {
        Route::post('tournaments/{tournament}/teams', [TeamController::class, 'store'])
            ->name('tournaments.teams.store');
    });

    Route::get('tournaments/{tournament}', [TournamentController::class, 'show'])->name('tournaments.show');

    // The visual bracket for a category — any authenticated club member may view it.
    Route::get('categories/{category}/bracket', [BracketController::class, 'show'])->name('tournaments.bracket');

    // A player's liability waiver for a tournament — view + sign their own.
    Route::get('tournaments/{tournament}/waiver', [WaiverController::class, 'show'])->name('tournaments.waiver');
    Route::post('tournaments/{tournament}/waiver', [WaiverController::class, 'store'])->name('tournaments.waiver.store');

    // Entrant self-service — any authenticated club member.
    Route::post('tournaments/categories/{category}/registrations', [RegistrationController::class, 'store'])
        ->name('tournaments.registrations.store');
    Route::delete('tournaments/registrations/{registration}', [RegistrationController::class, 'destroy'])
        ->name('tournaments.registrations.destroy');
});
