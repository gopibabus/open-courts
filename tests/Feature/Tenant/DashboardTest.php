<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function club(string $slug = 'alpha'): Tenant
    {
        $club = Tenant::create(['id' => $slug, 'name' => 'Alpha Club', 'slug' => $slug]);
        $club->domains()->create(['domain' => $slug]);

        return $club;
    }

    public function test_the_club_dashboard_renders_for_a_member(): void
    {
        $club = $this->club();
        $member = User::factory()->create();
        $club->users()->attach($member->id);

        $response = $this->withoutVite()->actingAs($member)->get('http://alpha.localhost/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('tenant/dashboard')
            ->where('club.name', 'Alpha Club')
            ->has('stats')
            ->has('capabilities')
            ->has('bookingsByDay', 7)
            ->has('courtUsage')
            ->has('you.reservations')
            ->has('you.stats')
        );
    }

    public function test_a_brand_new_club_dashboard_has_zeroed_stats_and_no_tournament(): void
    {
        $club = $this->club('beta');
        $member = User::factory()->create();
        $club->users()->attach($member->id);

        $response = $this->withoutVite()->actingAs($member)->get('http://beta.localhost/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('tenant/dashboard')
            ->where('stats.members', 1)
            ->where('stats.courts', 0)
            ->where('stats.bookingsThisWeek', 0)
            ->where('nextTournament', null)
            ->where('courtUsage.pct', 0)
        );
    }
}
