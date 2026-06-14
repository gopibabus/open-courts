<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Facilities\Models\Court;
use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class DemoSeeder extends Seeder
{
    /**
     * Seed a minimal, runnable demo: one platform admin and one club with an admin
     * and a couple of courts. Everything here is idempotent so it is safe to re-run.
     */
    public function run(): void
    {
        // 1. Platform operator — belongs to no single club, bypasses all checks.
        $platformAdmin = User::firstOrCreate(
            ['email' => 'admin@opentennis.test'],
            ['name' => 'Platform Admin', 'password' => Hash::make('password')],
        );
        $platformAdmin->forceFill(['is_platform_admin' => true])->save();

        // 2. A demo club, reachable locally at http://smashclub.localhost.
        //    We pin id == slug for a readable demo; new clubs created via the app
        //    will get an auto-generated UUID instead.
        $club = Tenant::firstOrCreate(
            ['slug' => 'smashclub'],
            ['id' => 'smashclub', 'name' => 'Smash Tennis Club'],
        );
        $club->domains()->firstOrCreate(['domain' => 'smashclub']);

        // 3. This club's roles + permissions.
        app(RolePermissionSeeder::class)->seedForTenant($club);

        // 4. A club admin, granted the 'club-admin' role *within this club*.
        $clubAdmin = User::firstOrCreate(
            ['email' => 'owner@smashclub.test'],
            ['name' => 'Sasha Owner', 'password' => Hash::make('password')],
        );
        $club->users()->syncWithoutDetaching([$clubAdmin->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($club->getTenantKey());
        $clubAdmin->assignRole('club-admin');

        // 5. A couple of courts — created inside the tenant context so BelongsToTenant
        //    stamps tenant_id automatically.
        tenancy()->initialize($club);
        Court::firstOrCreate(['name' => 'Center Court'], ['surface' => 'hard']);
        Court::firstOrCreate(['name' => 'Court 2'], ['surface' => 'clay']);
        tenancy()->end();
    }
}
