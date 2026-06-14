<?php

use App\Http\Controllers\Onboarding\RegisterClubController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
| Central (platform) routes — served only on the central domain (e.g. localhost,
| opentennis.test). Constrained to the central domain so they don't shadow the
| tenant routes defined in routes/tenant.php for <club>.<central>.
*/
Route::domain(config('tenancy.central_domain'))->group(function () {
    Route::get('/', function () {
        return Inertia::render('welcome');
    })->name('home');

    // Design-system gallery (living reference for the monochrome / dot-matrix system).
    Route::get('ui', fn () => Inertia::render('ui-gallery'))->name('ui.gallery');

    // Club onboarding — public signup that provisions a tenant + owner + roles.
    Route::middleware('guest')->group(function () {
        Route::get('register-club', [RegisterClubController::class, 'create'])->name('register-club.create');
        Route::post('register-club', [RegisterClubController::class, 'store'])->name('register-club.store');
    });

    Route::middleware(['auth'])->group(function () {
        Route::get('dashboard', function () {
            return Inertia::render('dashboard');
        })->name('dashboard');
    });
});

// Auth + account-settings routes stay universal so members can sign in on their
// club subdomain as well as on the central domain.
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
