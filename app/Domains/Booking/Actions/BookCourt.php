<?php

declare(strict_types=1);

namespace App\Domains\Booking\Actions;

use App\Domains\Booking\Data\BookCourtData;
use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Events\BookingConfirmed;
use App\Domains\Booking\Events\BookingRequested;
use App\Domains\Booking\Exceptions\CourtUnavailable;
use App\Domains\Booking\Models\Booking;
use App\Domains\Facilities\Models\Court;
use App\Domains\Facilities\Models\CourtAvailability;
use App\Domains\Facilities\Models\CourtBlackout;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The conflict-free core of the Booking context. Books a court for a member after
 * proving the window is bookable, all inside one row-locking transaction:
 *
 *   1. The court exists in this club and is active.
 *   2. starts_at < ends_at (defensive — the FormRequest already enforces this).
 *   3. The window lies entirely within ONE of the court's weekly availability windows
 *      for the booking day's day_of_week (0=Mon..6=Sun). Half-open [start, end):
 *      opens_at <= start-time AND end-time <= closes_at, both on the same calendar day.
 *   4. The window does not overlap any blackout — court-specific OR whole-club
 *      (court_id null). Overlap = (a_start < b_end) AND (b_start < a_end).
 *   5. The window does not overlap any *reserved* booking on the same court. The
 *      candidate rows are SELECT … FOR UPDATE locked first, so two concurrent
 *      bookers serialise: the loser sees the winner's row and is rejected.
 *
 * On any failure a CourtUnavailable is thrown (the transaction rolls back). On success
 * the booking is created 'reserved' and BookingRequested + BookingConfirmed fire after
 * commit. All times are half-open [starts_at, ends_at): back-to-back bookings that
 * merely touch at an endpoint do NOT conflict.
 */
final class BookCourt
{
    public function handle(int $userId, BookCourtData $data): Booking
    {
        $startsAt = Carbon::parse($data->startsAt);
        $endsAt = Carbon::parse($data->endsAt);

        if ($startsAt->greaterThanOrEqualTo($endsAt)) {
            throw CourtUnavailable::outsideAvailability();
        }

        return DB::transaction(function () use ($userId, $data, $startsAt, $endsAt): Booking {
            // Tenant-scoped via BelongsToTenant: only this club's courts are visible.
            $court = Court::query()->find($data->courtId);

            if ($court === null || ! $court->is_active) {
                throw CourtUnavailable::inactiveCourt();
            }

            $this->assertWithinAvailability($court, $startsAt, $endsAt);
            $this->assertNotBlackedOut($court, $startsAt, $endsAt);
            $this->assertNoOverlap($court, $startsAt, $endsAt);

            $booking = Booking::create([
                'court_id' => $court->getKey(),
                'user_id' => $userId,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => BookingStatus::Reserved,
                'price_cents' => $data->priceCents,
                'currency' => $data->currency,
            ]);

            BookingRequested::dispatch($booking);
            BookingConfirmed::dispatch($booking);

            return $booking;
        });
    }

    /**
     * The whole window must fit inside ONE weekly availability window for the
     * booking day's day_of_week. day_of_week is 0=Mon..6=Sun; Carbon::dayOfWeek is
     * 0=Sun..6=Sat, so it is remapped. opens_at/closes_at are time-of-day and are
     * compared against the booking's time-of-day on the same calendar day.
     */
    private function assertWithinAvailability(Court $court, Carbon $startsAt, Carbon $endsAt): void
    {
        // Carbon: Sunday=0..Saturday=6  ->  domain: Monday=0..Sunday=6.
        $dayOfWeek = ($startsAt->dayOfWeek + 6) % 7;

        $windows = CourtAvailability::query()
            ->where('court_id', $court->getKey())
            ->where('day_of_week', $dayOfWeek)
            ->get();

        foreach ($windows as $window) {
            /** @var CourtAvailability $window */
            $opensAt = $startsAt->copy()->setTimeFromTimeString($window->opens_at->format('H:i:s'));
            $closesAt = $startsAt->copy()->setTimeFromTimeString($window->closes_at->format('H:i:s'));

            if ($startsAt->greaterThanOrEqualTo($opensAt) && $endsAt->lessThanOrEqualTo($closesAt)) {
                return;
            }
        }

        throw CourtUnavailable::outsideAvailability();
    }

    /** Reject if the window overlaps any blackout on this court or the whole club. */
    private function assertNotBlackedOut(Court $court, Carbon $startsAt, Carbon $endsAt): void
    {
        $clash = CourtBlackout::query()
            ->where(function ($q) use ($court): void {
                $q->where('court_id', $court->getKey())
                    ->orWhereNull('court_id'); // whole-club blackout
            })
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        if ($clash) {
            throw CourtUnavailable::duringBlackout();
        }
    }

    /**
     * Reject if the window overlaps any *reserved* booking on this court. The
     * candidate rows are locked FOR UPDATE so concurrent bookers serialise on them.
     */
    private function assertNoOverlap(Court $court, Carbon $startsAt, Carbon $endsAt): void
    {
        $clash = Booking::query()
            ->where('court_id', $court->getKey())
            ->where('status', BookingStatus::Reserved)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->lockForUpdate()
            ->exists();

        if ($clash) {
            throw CourtUnavailable::alreadyBooked();
        }
    }
}
