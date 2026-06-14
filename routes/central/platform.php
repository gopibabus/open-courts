<?php

declare(strict_types=1);

use App\Http\Controllers\Platform\ClubController;
use App\Http\Controllers\Platform\ImpersonationController;
use App\Http\Middleware\EnsurePlatformAdmin;
use Illuminate\Support\Facades\Route;

/*
| Platform-admin (central) routes — operate across ALL clubs from the central domain.
|
| This file is required from inside the central-domain group in routes/web.php
| (the `glob(routes/central/*.php)` loop), so it is already constrained to
| config('tenancy.central_domain'). `tenant()` is null here — we work on Tenant models
| directly. Every route is double-guarded: `auth` (redirect anonymous to login) +
| EnsurePlatformAdmin (403 for any signed-in non-platform-admin).
*/
Route::middleware(['auth', EnsurePlatformAdmin::class])
    ->prefix('admin')
    ->name('platform.')
    ->group(function () {
        Route::get('clubs', [ClubController::class, 'index'])->name('clubs.index');
        Route::get('clubs/{club}', [ClubController::class, 'show'])->name('clubs.show');
        Route::post('clubs/{club}/suspend', [ClubController::class, 'suspend'])->name('clubs.suspend');
        Route::post('clubs/{club}/reactivate', [ClubController::class, 'reactivate'])->name('clubs.reactivate');

        // Begin impersonating a club's owner → bounce to the club subdomain.
        Route::post('clubs/{club}/impersonate', [ImpersonationController::class, 'store'])->name('clubs.impersonate');
    });
