<?php

namespace Tests\Feature\Booking;

use App\Domains\Booking\Models\Booking;
use App\Domains\Facilities\Models\Court;
use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTimeSerializationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Booking times are club wall-clock times. They must be sent to the client WITHOUT a
     * timezone offset so the browser renders them as the same wall-clock hour the member
     * picked (an offset makes new Date() shift them to the browser's timezone — e.g. a
     * 10:00 booking displaying as 06:00). See the live-debug timezone bug.
     */
    public function test_booking_times_are_naive_wall_clock_without_a_utc_offset(): void
    {
        $club = Tenant::create(['id' => 'alpha', 'name' => 'Alpha Club', 'slug' => 'alpha']);
        $club->domains()->create(['domain' => 'alpha']);

        $member = User::factory()->create();
        $club->users()->attach($member->id);

        tenancy()->initialize($club);
        $court = Court::create(['name' => 'Center Court', 'surface' => 'hard']);
        Booking::create([
            'court_id' => $court->id,
            'user_id' => $member->id,
            'starts_at' => '2026-06-15 10:00:00',
            'ends_at' => '2026-06-15 11:00:00',
            'status' => 'reserved',
        ]);
        tenancy()->end();

        $response = $this->withoutVite()->actingAs($member)->get('http://alpha.localhost/bookings');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('booking/index')
            ->where('myBookings.0.starts_at', '2026-06-15T10:00:00')
            ->where('myBookings.0.ends_at', '2026-06-15T11:00:00')
        );
    }
}
