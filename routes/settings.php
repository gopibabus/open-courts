<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Middleware\InitializeTenancyForUniversalRoutes;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Account settings are universal: the same routes serve the central app AND every club
// subdomain. On a club subdomain we initialize tenancy first (no-op centrally) so these
// pages render inside the club shell — a seamless transition to/from the dashboard.
Route::middleware([InitializeTenancyForUniversalRoutes::class, 'auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance');
});
