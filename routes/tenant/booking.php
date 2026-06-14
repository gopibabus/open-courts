<?php

declare(strict_types=1);

use App\Http\Controllers\Booking\BookingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Booking (Court reservations) — tenant routes
|--------------------------------------------------------------------------
|
| Auto-required by routes/tenant.php INSIDE the tenant + subdomain group, but
| OUTSIDE 'auth', so this file applies its own 'auth' middleware. Served on
| <club>.<central_domain>. tenant() is the resolved club; auth()->user() the
| signed-in member.
|
| - Any authenticated member may VIEW the booking screen + their own bookings.
| - Creating a booking requires the club-scoped `court.book` permission.
| - Cancelling is allowed for the OWNER (checked in the controller); cancelling
|   another member's booking additionally needs `booking.manage`.
|
| The {tenant} domain param is forgotten at request time (ForgetTenantRouteParameter),
| so normal route-model binding (Booking $booking) and route('bookings.*') just work.
|
*/

Route::middleware('auth')->group(function () {
    // Read — any authenticated member: the booking screen + their own bookings.
    Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');

    // Create a booking — requires the club-scoped `court.book` permission.
    Route::middleware('can:court.book')->group(function () {
        Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');
    });

    // Cancel — owner-or-booking.manage is enforced inside the controller.
    Route::delete('bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');
});
