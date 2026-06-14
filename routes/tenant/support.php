<?php

declare(strict_types=1);

use App\Http\Controllers\Support\HelpController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Support (Help) — tenant routes
|--------------------------------------------------------------------------
|
| Auto-required by routes/tenant.php INSIDE the tenant + subdomain group, but
| OUTSIDE 'auth', so this file applies its own 'auth' middleware. Served on
| <club>.<central_domain>.
|
| Any authenticated club member may view the Help page and file a support
| request — no club-scoped permission is required.
|
*/

Route::middleware('auth')->group(function () {
    Route::get('help', [HelpController::class, 'index'])->name('help.index');
    Route::post('help', [HelpController::class, 'store'])->name('help.store');
});
