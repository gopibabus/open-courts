<?php

namespace Tests\Feature\Booking;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Events\BookingCancelled;
use App\Domains\Booking\Events\BookingConfirmed;
use App\Domains\Booking\Listeners\SendBookingConfirmationEmail;
use App\Domains\Booking\Models\Booking;
use App\Domains\Facilities\Models\Court;
use App\Domains\Facilities\Models\CourtAvailability;
use App\Domains\Facilities\Models\CourtBlackout;
use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Mail\BookingConfirmationMail;
use App\Domains\Tenancy\Models\Tenant;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    /** Create a club with its domain + roles seeded, returning the tenant. */
    private function makeClub(string $slug): Tenant
    {
        $club = Tenant::create([
            'id' => $slug,
            'name' => ucfirst($slug).' Club',
            'slug' => $slug,
        ]);
        $club->domains()->create(['domain' => $slug]);

        $roleSeeder = app(RolePermissionSeeder::class);
        $roleSeeder->run();
        $roleSeeder->seedForTenant($club);

        return $club;
    }

    /** Create a member of $club and give them $role in that club's team context. */
    private function makeMember(Tenant $club, string $role): User
    {
        $user = User::factory()->create();
        $club->users()->attach($user->id);

        app(PermissionRegistrar::class)->setPermissionsTeamId($club->getTenantKey());
        $user->assignRole($role);

        return $user;
    }

    /**
     * Create an always-open court (every weekday 00:00–23:00) in $club and return it.
     * Times are wall-clock so a fixed test datetime always falls inside a window.
     */
    private function makeOpenCourt(Tenant $club, string $name = 'Court 1'): Court
    {
        tenancy()->initialize($club);
        $court = Court::create(['name' => $name, 'is_active' => true]);
        for ($day = 0; $day <= 6; $day++) {
            CourtAvailability::create([
                'court_id' => $court->id,
                'day_of_week' => $day,
                'opens_at' => '00:00',
                'closes_at' => '23:00',
            ]);
        }
        tenancy()->end();

        return $court;
    }

    /** A datetime payload for "tomorrow" at the given hours (avoids past-time edge cases). */
    private function window(int $startHour, int $endHour): array
    {
        $day = Carbon::tomorrow();

        return [
            'starts_at' => $day->copy()->setTime($startHour, 0)->format('Y-m-d H:i:s'),
            'ends_at' => $day->copy()->setTime($endHour, 0)->format('Y-m-d H:i:s'),
        ];
    }

    public function test_member_with_court_book_can_book_an_available_slot(): void
    {
        Event::fake([BookingConfirmed::class]);

        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member');
        $court = $this->makeOpenCourt($club);
        $slot = $this->window(10, 11);

        $response = $this->actingAs($member)->post('http://alpha.localhost/bookings', [
            'court_id' => $court->id,
            ...$slot,
        ]);

        $response->assertRedirect();

        tenancy()->initialize($club);
        $booking = Booking::firstOrFail();
        $this->assertSame($court->id, $booking->court_id);
        $this->assertSame($member->id, $booking->user_id);
        $this->assertSame(BookingStatus::Reserved, $booking->status);
        $this->assertSame($club->getTenantKey(), $booking->tenant_id);
        tenancy()->end();

        Event::assertDispatched(BookingConfirmed::class, fn (BookingConfirmed $e) => $e->booking->is($booking));
    }

    public function test_overlapping_double_booking_is_rejected(): void
    {
        $club = $this->makeClub('alpha');
        $first = $this->makeMember($club, 'member');
        $second = $this->makeMember($club, 'member');
        $court = $this->makeOpenCourt($club);

        // 10:00–12:00 booked.
        $this->actingAs($first)->post('http://alpha.localhost/bookings', [
            'court_id' => $court->id,
            ...$this->window(10, 12),
        ])->assertRedirect();

        // 11:00–13:00 overlaps the first hour — must be rejected (422 validation error).
        $this->actingAs($second)->post('http://alpha.localhost/bookings', [
            'court_id' => $court->id,
            ...$this->window(11, 13),
        ])->assertSessionHasErrors('booking');

        tenancy()->initialize($club);
        $this->assertSame(1, Booking::count());
        tenancy()->end();
    }

    public function test_back_to_back_bookings_do_not_conflict(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member');
        $court = $this->makeOpenCourt($club);

        $this->actingAs($member)->post('http://alpha.localhost/bookings', [
            'court_id' => $court->id,
            ...$this->window(10, 11),
        ])->assertRedirect();

        // 11:00–12:00 touches the previous booking's end — half-open, so NO conflict.
        $this->actingAs($member)->post('http://alpha.localhost/bookings', [
            'court_id' => $court->id,
            ...$this->window(11, 12),
        ])->assertRedirect();

        tenancy()->initialize($club);
        $this->assertSame(2, Booking::count());
        tenancy()->end();
    }

    public function test_booking_outside_availability_is_rejected(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member');

        // Court open only 08:00–10:00 every day.
        tenancy()->initialize($club);
        $court = Court::create(['name' => 'Court 1', 'is_active' => true]);
        for ($day = 0; $day <= 6; $day++) {
            CourtAvailability::create([
                'court_id' => $court->id,
                'day_of_week' => $day,
                'opens_at' => '08:00',
                'closes_at' => '10:00',
            ]);
        }
        tenancy()->end();

        // 14:00–15:00 is outside the open window.
        $this->actingAs($member)->post('http://alpha.localhost/bookings', [
            'court_id' => $court->id,
            ...$this->window(14, 15),
        ])->assertSessionHasErrors('booking');

        tenancy()->initialize($club);
        $this->assertSame(0, Booking::count());
        tenancy()->end();
    }

    public function test_booking_during_a_blackout_is_rejected(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member');
        $court = $this->makeOpenCourt($club);
        $slot = $this->window(10, 11);

        tenancy()->initialize($club);
        CourtBlackout::create([
            'court_id' => $court->id,
            'starts_at' => Carbon::tomorrow()->setTime(9, 0),
            'ends_at' => Carbon::tomorrow()->setTime(12, 0),
            'reason' => 'Maintenance',
        ]);
        tenancy()->end();

        $this->actingAs($member)->post('http://alpha.localhost/bookings', [
            'court_id' => $court->id,
            ...$slot,
        ])->assertSessionHasErrors('booking');

        tenancy()->initialize($club);
        $this->assertSame(0, Booking::count());
        tenancy()->end();
    }

    public function test_whole_club_blackout_blocks_booking(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member');
        $court = $this->makeOpenCourt($club);

        // court_id null = whole-club blackout.
        tenancy()->initialize($club);
        CourtBlackout::create([
            'court_id' => null,
            'starts_at' => Carbon::tomorrow()->setTime(0, 0),
            'ends_at' => Carbon::tomorrow()->setTime(23, 59),
            'reason' => 'Holiday',
        ]);
        tenancy()->end();

        $this->actingAs($member)->post('http://alpha.localhost/bookings', [
            'court_id' => $court->id,
            ...$this->window(10, 11),
        ])->assertSessionHasErrors('booking');

        tenancy()->initialize($club);
        $this->assertSame(0, Booking::count());
        tenancy()->end();
    }

    public function test_member_can_cancel_their_own_booking(): void
    {
        Event::fake([BookingCancelled::class]);

        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member');
        $court = $this->makeOpenCourt($club);

        tenancy()->initialize($club);
        $booking = Booking::create([
            'court_id' => $court->id,
            'user_id' => $member->id,
            'starts_at' => Carbon::tomorrow()->setTime(10, 0),
            'ends_at' => Carbon::tomorrow()->setTime(11, 0),
            'status' => BookingStatus::Reserved,
        ]);
        tenancy()->end();

        $this->actingAs($member)
            ->delete("http://alpha.localhost/bookings/{$booking->id}")
            ->assertRedirect();

        tenancy()->initialize($club);
        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        tenancy()->end();

        Event::assertDispatched(BookingCancelled::class);
    }

    public function test_member_cannot_cancel_another_members_booking(): void
    {
        $club = $this->makeClub('alpha');
        $owner = $this->makeMember($club, 'member');
        $intruder = $this->makeMember($club, 'member');
        $court = $this->makeOpenCourt($club);

        tenancy()->initialize($club);
        $booking = Booking::create([
            'court_id' => $court->id,
            'user_id' => $owner->id,
            'starts_at' => Carbon::tomorrow()->setTime(10, 0),
            'ends_at' => Carbon::tomorrow()->setTime(11, 0),
            'status' => BookingStatus::Reserved,
        ]);
        tenancy()->end();

        $this->actingAs($intruder)
            ->delete("http://alpha.localhost/bookings/{$booking->id}")
            ->assertForbidden();

        tenancy()->initialize($club);
        $this->assertSame(BookingStatus::Reserved, $booking->fresh()->status);
        tenancy()->end();
    }

    public function test_booking_manage_can_cancel_another_members_booking(): void
    {
        $club = $this->makeClub('alpha');
        $owner = $this->makeMember($club, 'member');
        // club-admin has booking.manage (full permission set).
        $manager = $this->makeMember($club, 'club-admin');
        $court = $this->makeOpenCourt($club);

        tenancy()->initialize($club);
        $booking = Booking::create([
            'court_id' => $court->id,
            'user_id' => $owner->id,
            'starts_at' => Carbon::tomorrow()->setTime(10, 0),
            'ends_at' => Carbon::tomorrow()->setTime(11, 0),
            'status' => BookingStatus::Reserved,
        ]);
        tenancy()->end();

        $this->actingAs($manager)
            ->delete("http://alpha.localhost/bookings/{$booking->id}")
            ->assertRedirect();

        tenancy()->initialize($club);
        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        tenancy()->end();
    }

    public function test_member_without_court_book_cannot_book(): void
    {
        $club = $this->makeClub('alpha');
        // A bare club member with NO role -> no court.book permission.
        $user = User::factory()->create();
        $club->users()->attach($user->id);
        $court = $this->makeOpenCourt($club);

        $this->actingAs($user)->post('http://alpha.localhost/bookings', [
            'court_id' => $court->id,
            ...$this->window(10, 11),
        ])->assertForbidden();

        tenancy()->initialize($club);
        $this->assertSame(0, Booking::count());
        tenancy()->end();
    }

    public function test_cancelled_slot_can_be_rebooked(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member');
        $court = $this->makeOpenCourt($club);
        $slot = $this->window(10, 11);

        // Book, then cancel.
        $this->actingAs($member)->post('http://alpha.localhost/bookings', [
            'court_id' => $court->id,
            ...$slot,
        ])->assertRedirect();

        tenancy()->initialize($club);
        $booking = Booking::firstOrFail();
        tenancy()->end();

        $this->actingAs($member)->delete("http://alpha.localhost/bookings/{$booking->id}")->assertRedirect();

        // Same window is free again — a cancelled booking does not block.
        $this->actingAs($member)->post('http://alpha.localhost/bookings', [
            'court_id' => $court->id,
            ...$slot,
        ])->assertRedirect();

        tenancy()->initialize($club);
        $this->assertSame(1, Booking::where('status', BookingStatus::Reserved)->count());
        tenancy()->end();
    }

    public function test_bookings_are_isolated_between_clubs(): void
    {
        $alpha = $this->makeClub('alpha');
        $beta = $this->makeClub('beta');

        $alphaCourt = $this->makeOpenCourt($alpha);
        $betaMember = $this->makeMember($beta, 'member');

        // Beta's member cannot book Alpha's court (court_id fails the tenant-scoped exists rule).
        $this->actingAs($betaMember)->post('http://beta.localhost/bookings', [
            'court_id' => $alphaCourt->id,
            ...$this->window(10, 11),
        ])->assertSessionHasErrors('court_id');

        tenancy()->initialize($alpha);
        $this->assertSame(0, Booking::count());
        tenancy()->end();
    }

    public function test_confirmation_email_is_sent_for_a_confirmed_booking(): void
    {
        Mail::fake();

        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member');
        $court = $this->makeOpenCourt($club);

        tenancy()->initialize($club);
        $booking = Booking::create([
            'court_id' => $court->id,
            'user_id' => $member->id,
            'starts_at' => Carbon::tomorrow()->setTime(10, 0),
            'ends_at' => Carbon::tomorrow()->setTime(11, 0),
            'status' => BookingStatus::Reserved,
        ]);
        tenancy()->end();

        (new SendBookingConfirmationEmail)->handle(new BookingConfirmed($booking));

        Mail::assertSent(
            BookingConfirmationMail::class,
            fn (BookingConfirmationMail $m) => $m->hasTo($member->email),
        );
    }
}
