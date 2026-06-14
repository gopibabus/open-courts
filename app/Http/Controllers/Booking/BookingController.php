<?php

declare(strict_types=1);

namespace App\Http\Controllers\Booking;

use App\Domains\Booking\Actions\BookCourt;
use App\Domains\Booking\Actions\CancelBooking;
use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Exceptions\CourtUnavailable;
use App\Domains\Booking\Models\Booking;
use App\Domains\Facilities\Models\Court;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Member-facing court booking. Any authenticated member may VIEW the booking screen
 * and their own bookings; creating a booking needs `court.book` (route-gated). A member
 * may cancel their OWN booking; cancelling anyone else's needs `booking.manage`.
 *
 * Every query here is tenant-scoped by BelongsToTenant, so a member can only ever see
 * or touch their own club's courts and bookings.
 */
class BookingController extends Controller
{
    /**
     * The booking screen: every court (with weekly windows + blackouts) so the client
     * can render open slots, the court's upcoming reserved bookings so taken slots show
     * as unavailable, and the current member's own bookings for the "my bookings" list.
     *
     * Datetimes are serialized as NAIVE wall-clock ("Y-m-d\TH:i:s", no offset): booking
     * times are club-local wall-clock, so an offset would make the client's new Date()
     * shift them into the browser's timezone (e.g. a 10:00 booking showing as 06:00).
     */
    public function index(): Response
    {
        $userId = (int) auth()->id();

        $courts = Court::query()
            ->where('is_active', true)
            ->with([
                'availability' => fn ($q) => $q->orderBy('day_of_week')->orderBy('opens_at'),
                'blackouts' => fn ($q) => $q->orderBy('starts_at'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Court $court) => [
                'id' => $court->id,
                'name' => $court->name,
                'surface' => $court->surface,
                'availability' => $court->availability->map(fn ($w) => [
                    'day_of_week' => $w->day_of_week,
                    'opens_at' => $w->opens_at?->format('H:i'),
                    'closes_at' => $w->closes_at?->format('H:i'),
                ])->values(),
                'blackouts' => $court->blackouts->map(fn ($b) => [
                    'starts_at' => $b->starts_at?->format('Y-m-d\TH:i:s'),
                    'ends_at' => $b->ends_at?->format('Y-m-d\TH:i:s'),
                ])->values(),
            ]);

        // Upcoming reserved bookings across all courts — drives "taken slot" rendering.
        $courtBookings = Booking::query()
            ->where('status', BookingStatus::Reserved)
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at')
            ->get(['id', 'court_id', 'starts_at', 'ends_at'])
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'court_id' => $b->court_id,
                'starts_at' => $b->starts_at?->format('Y-m-d\TH:i:s'),
                'ends_at' => $b->ends_at?->format('Y-m-d\TH:i:s'),
            ]);

        // The signed-in member's own bookings (any status), newest window first.
        $myBookings = Booking::query()
            ->where('user_id', $userId)
            ->with('court:id,name')
            ->orderByDesc('starts_at')
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'court_id' => $b->court_id,
                'court_name' => $b->court?->name,
                'starts_at' => $b->starts_at?->format('Y-m-d\TH:i:s'),
                'ends_at' => $b->ends_at?->format('Y-m-d\TH:i:s'),
                'status' => $b->status->value,
                'can_cancel' => $b->status === BookingStatus::Reserved,
            ]);

        return Inertia::render('booking/index', [
            'courts' => $courts,
            'courtBookings' => $courtBookings,
            'myBookings' => $myBookings,
        ]);
    }

    /**
     * Book a court. Domain rejections (inactive court, outside availability, blackout,
     * overlap) surface as a 422 validation error on `booking`.
     */
    public function store(StoreBookingRequest $request, BookCourt $bookCourt): RedirectResponse
    {
        try {
            $bookCourt->handle((int) $request->user()->getKey(), $request->toData());
        } catch (CourtUnavailable $e) {
            throw ValidationException::withMessages(['booking' => $e->getMessage()]);
        }

        return back()->with('status', 'Booking confirmed.');
    }

    /**
     * Cancel a booking. A member may cancel their own; cancelling anyone else's needs
     * the club-scoped `booking.manage` permission. The {booking} binding is
     * tenant-scoped, so this can never reach another club's booking.
     */
    public function destroy(Booking $booking, CancelBooking $cancelBooking): RedirectResponse
    {
        $user = request()->user();

        abort_unless(
            $booking->user_id === $user->getKey() || $user->can('booking.manage'),
            403,
        );

        $cancelBooking->handle($booking);

        return back()->with('status', 'Booking cancelled.');
    }
}
