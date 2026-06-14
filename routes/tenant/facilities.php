<?php

declare(strict_types=1);

use App\Http\Controllers\Facilities\CourtAvailabilityController;
use App\Http\Controllers\Facilities\CourtBlackoutController;
use App\Http\Controllers\Facilities\CourtController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Facilities (Courts & Availability) — tenant routes
|--------------------------------------------------------------------------
|
| Auto-required by routes/tenant.php INSIDE the tenant + subdomain group, but
| OUTSIDE 'auth', so this file applies its own 'auth' middleware. Served on
| <club>.<central_domain>. tenant() is the resolved club; auth()->user() the
| signed-in member.
|
| Any authenticated member may VIEW courts. Mutating routes are gated behind the
| club-scoped `court.manage` permission via the `can:` middleware.
|
*/

Route::middleware('auth')->group(function () {
    // Read — any authenticated member.
    Route::get('courts', [CourtController::class, 'index'])->name('courts.index');

    // Write — requires the club-scoped `court.manage` permission.
    Route::middleware('can:court.manage')->group(function () {
        Route::post('courts', [CourtController::class, 'store'])->name('courts.store');
        Route::put('courts/{court}', [CourtController::class, 'update'])->name('courts.update');
        Route::delete('courts/{court}', [CourtController::class, 'destroy'])->name('courts.destroy');

        Route::put('courts/{court}/availability', [CourtAvailabilityController::class, 'update'])
            ->name('courts.availability.update');

        Route::post('blackouts', [CourtBlackoutController::class, 'store'])->name('blackouts.store');
        Route::delete('blackouts/{blackout}', [CourtBlackoutController::class, 'destroy'])
            ->name('blackouts.destroy');
    });
});
