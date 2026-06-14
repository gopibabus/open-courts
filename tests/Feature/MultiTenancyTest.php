<?php

namespace Tests\Feature;

use App\Domains\Facilities\Models\Court;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Identity\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    private function makeClub(string $slug): Tenant
    {
        $club = Tenant::create([
            'id' => $slug,
            'name' => ucfirst($slug).' Club',
            'slug' => $slug,
        ]);
        $club->domains()->create(['domain' => $slug]);

        return $club;
    }

    public function test_courts_are_scoped_to_their_club(): void
    {
        $alpha = $this->makeClub('alpha');
        $beta = $this->makeClub('beta');

        tenancy()->initialize($alpha);
        Court::create(['name' => 'Alpha Center']);
        tenancy()->end();

        tenancy()->initialize($beta);
        Court::create(['name' => 'Beta Center']);
        $this->assertSame(1, Court::count());
        $this->assertSame('Beta Center', Court::first()->name);
        tenancy()->end();

        // Back in Alpha we only ever see Alpha's court.
        tenancy()->initialize($alpha);
        $this->assertSame(1, Court::count());
        $this->assertSame('Alpha Center', Court::first()->name);
        tenancy()->end();
    }

    public function test_roles_are_scoped_per_club(): void
    {
        $alpha = $this->makeClub('alpha');
        $beta = $this->makeClub('beta');

        $seeder = app(RolePermissionSeeder::class);
        $seeder->run(); // global permissions
        $seeder->seedForTenant($alpha);
        $seeder->seedForTenant($beta);

        $user = User::factory()->create();
        $registrar = app(PermissionRegistrar::class);

        // Make the user a club-admin in Alpha only.
        $registrar->setPermissionsTeamId($alpha->getTenantKey());
        $user->assignRole('club-admin');

        $user->unsetRelation('roles');
        $this->assertTrue($user->hasRole('club-admin'));

        // The same user has no role in Beta.
        $registrar->setPermissionsTeamId($beta->getTenantKey());
        $user->unsetRelation('roles');
        $this->assertFalse($user->hasRole('club-admin'));
    }

    public function test_platform_admin_bypasses_all_gates(): void
    {
        $club = $this->makeClub('alpha');
        app(RolePermissionSeeder::class)->run();

        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();

        // Even inside a club's team context, a platform admin passes every check.
        app(PermissionRegistrar::class)->setPermissionsTeamId($club->getTenantKey());
        $this->assertTrue(Gate::forUser($admin)->check('court.manage'));
    }

    public function test_subdomain_resolves_the_club_dashboard(): void
    {
        $club = $this->makeClub('alpha');
        $seeder = app(RolePermissionSeeder::class);
        $seeder->run();
        $seeder->seedForTenant($club);

        $user = User::factory()->create();
        $club->users()->attach($user->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($club->getTenantKey());
        $user->assignRole('member');

        // withoutVite() stubs the asset manifest so the Inertia root view renders
        // without a `npm run build` having been run.
        $response = $this->withoutVite()
            ->actingAs($user)
            ->get('http://alpha.localhost/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('tenant/dashboard')
            ->where('club.slug', 'alpha')
            ->where('roles', ['member'])
        );
    }
}
