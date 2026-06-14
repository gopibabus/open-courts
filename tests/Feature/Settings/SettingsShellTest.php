<?php

namespace Tests\Feature\Settings;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Tenant;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Account settings are universal routes. On a club subdomain they must initialize tenancy
 * so the page carries the `club` prop and renders inside the club shell (a seamless
 * transition to/from the dashboard); on the central domain there is no club.
 */
class SettingsShellTest extends TestCase
{
    use RefreshDatabase;

    private function makeClub(string $slug): Tenant
    {
        $club = Tenant::create(['id' => $slug, 'name' => ucfirst($slug).' Club', 'slug' => $slug]);
        $club->domains()->create(['domain' => $slug]);

        $seeder = app(RolePermissionSeeder::class);
        $seeder->run();
        $seeder->seedForTenant($club);

        return $club;
    }

    public function test_settings_on_a_club_subdomain_carries_the_club_for_the_shell(): void
    {
        $club = $this->makeClub('alpha');
        $member = User::factory()->create();
        $club->users()->attach($member->id);

        $this->actingAs($member)
            ->withoutVite()
            ->get('http://alpha.localhost/settings/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/profile')
                ->where('club.slug', 'alpha'));
    }

    public function test_settings_on_the_central_domain_has_no_club(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withoutVite()
            ->get('http://localhost/settings/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/profile')
                ->where('club', null));
    }
}
