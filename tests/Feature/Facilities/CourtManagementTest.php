<?php

namespace Tests\Feature\Facilities;

use App\Domains\Facilities\Models\Court;
use App\Domains\Facilities\Models\CourtAvailability;
use App\Domains\Facilities\Models\CourtBlackout;
use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Tenant;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CourtManagementTest extends TestCase
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

        $seeder = app(RolePermissionSeeder::class);
        $seeder->run();
        $seeder->seedForTenant($club);

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

    public function test_club_admin_can_create_a_court(): void
    {
        $club = $this->makeClub('alpha');
        $admin = $this->makeMember($club, 'club-admin');

        $response = $this->actingAs($admin)->post('http://alpha.localhost/courts', [
            'name' => 'Centre Court',
            'surface' => 'hard',
            'is_active' => true,
        ]);

        $response->assertRedirect();

        tenancy()->initialize($club);
        $court = Court::firstOrFail();
        $this->assertSame('Centre Court', $court->name);
        $this->assertSame('hard', $court->surface);
        $this->assertTrue($court->is_active);
        // BelongsToTenant stamps the tenant_id automatically.
        $this->assertSame($club->getTenantKey(), $court->tenant_id);
        tenancy()->end();
    }

    public function test_availability_can_be_set_for_a_court(): void
    {
        $club = $this->makeClub('alpha');
        $admin = $this->makeMember($club, 'club-admin');

        tenancy()->initialize($club);
        $court = Court::create(['name' => 'Court 1']);
        tenancy()->end();

        $response = $this->actingAs($admin)->put("http://alpha.localhost/courts/{$court->id}/availability", [
            'windows' => [
                ['day_of_week' => 0, 'opens_at' => '09:00', 'closes_at' => '21:00'],
                ['day_of_week' => 5, 'opens_at' => '08:00', 'closes_at' => '18:00'],
            ],
        ]);

        $response->assertRedirect();

        tenancy()->initialize($club);
        $this->assertSame(2, CourtAvailability::where('court_id', $court->id)->count());
        $window = CourtAvailability::where('court_id', $court->id)->where('day_of_week', 5)->firstOrFail();
        $this->assertSame($club->getTenantKey(), $window->tenant_id);
        tenancy()->end();
    }

    public function test_setting_availability_replaces_existing_windows(): void
    {
        $club = $this->makeClub('alpha');
        $admin = $this->makeMember($club, 'club-admin');

        tenancy()->initialize($club);
        $court = Court::create(['name' => 'Court 1']);
        CourtAvailability::create(['court_id' => $court->id, 'day_of_week' => 1, 'opens_at' => '06:00', 'closes_at' => '07:00']);
        tenancy()->end();

        $this->actingAs($admin)->put("http://alpha.localhost/courts/{$court->id}/availability", [
            'windows' => [
                ['day_of_week' => 2, 'opens_at' => '10:00', 'closes_at' => '12:00'],
            ],
        ])->assertRedirect();

        tenancy()->initialize($club);
        $this->assertSame(1, CourtAvailability::where('court_id', $court->id)->count());
        $this->assertSame(2, CourtAvailability::where('court_id', $court->id)->firstOrFail()->day_of_week);
        tenancy()->end();
    }

    public function test_blackout_can_be_added_for_a_court(): void
    {
        $club = $this->makeClub('alpha');
        $admin = $this->makeMember($club, 'club-admin');

        tenancy()->initialize($club);
        $court = Court::create(['name' => 'Court 1']);
        tenancy()->end();

        $response = $this->actingAs($admin)->post('http://alpha.localhost/blackouts', [
            'court_id' => $court->id,
            'starts_at' => '2026-07-01 08:00:00',
            'ends_at' => '2026-07-01 12:00:00',
            'reason' => 'Resurfacing',
        ]);

        $response->assertRedirect();

        tenancy()->initialize($club);
        $blackout = CourtBlackout::firstOrFail();
        $this->assertSame($court->id, $blackout->court_id);
        $this->assertSame('Resurfacing', $blackout->reason);
        $this->assertSame($club->getTenantKey(), $blackout->tenant_id);
        tenancy()->end();
    }

    public function test_whole_club_blackout_allows_null_court(): void
    {
        $club = $this->makeClub('alpha');
        $admin = $this->makeMember($club, 'club-admin');

        $this->actingAs($admin)->post('http://alpha.localhost/blackouts', [
            'court_id' => null,
            'starts_at' => '2026-12-24 00:00:00',
            'ends_at' => '2026-12-26 23:59:00',
            'reason' => 'Holiday',
        ])->assertRedirect();

        tenancy()->initialize($club);
        $this->assertNull(CourtBlackout::firstOrFail()->court_id);
        tenancy()->end();
    }

    public function test_member_without_court_manage_is_forbidden(): void
    {
        $club = $this->makeClub('alpha');
        // 'member' role has only court.book — not court.manage.
        $member = $this->makeMember($club, 'member');

        $this->actingAs($member)->post('http://alpha.localhost/courts', [
            'name' => 'Sneaky Court',
            'surface' => 'clay',
            'is_active' => true,
        ])->assertForbidden();

        tenancy()->initialize($club);
        $this->assertSame(0, Court::count());
        tenancy()->end();
    }

    public function test_any_member_can_view_courts(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        Court::create(['name' => 'Court 1']);
        tenancy()->end();

        $this->withoutVite()
            ->actingAs($member)
            ->get('http://alpha.localhost/courts')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('facilities/courts/index')
                ->where('canManage', false)
                ->has('courts', 1)
            );
    }

    public function test_courts_are_isolated_between_clubs(): void
    {
        $alpha = $this->makeClub('alpha');
        $beta = $this->makeClub('beta');

        tenancy()->initialize($alpha);
        Court::create(['name' => 'Alpha Court']);
        tenancy()->end();

        $betaAdmin = $this->makeMember($beta, 'club-admin');

        // Beta's admin only ever sees Beta's (empty) court list.
        $this->withoutVite()
            ->actingAs($betaAdmin)
            ->get('http://beta.localhost/courts')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('facilities/courts/index')
                ->where('canManage', true)
                ->has('courts', 0)
            );
    }
}
