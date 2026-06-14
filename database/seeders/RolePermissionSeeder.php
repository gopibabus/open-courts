<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Every action a club can authorize. Permissions are GLOBAL in spatie (no team
     * scope) — only the role → permission *assignments* differ per club. Adjust this
     * list as the feature set grows.
     */
    public const PERMISSIONS = [
        'club.manage',        // edit club settings, branding
        'member.manage',      // invite/remove members, assign their roles
        'court.manage',       // add/edit/deactivate courts
        'court.book',         // create a booking
        'booking.manage',     // cancel/override other members' bookings
        'tournament.manage',  // create and run tournaments
        'team.manage',        // create teams, manage rosters
    ];

    /**
     * The club role matrix: which role grants which permissions, applied per club.
     *
     * ┌───────────────────────────────────────────────────────────────────────────┐
     * │ TODO(you): this is YOUR domain decision and it shapes the whole auth model. │
     * │ The starter below is a reasonable guess — refine it to match how your clubs │
     * │ actually run. Things to weigh:                                              │
     * │   • Do you need a 'referee'/'umpire' role for tournament scoring?           │
     * │   • Should 'court.book' be split (member vs. guest, peak vs. off-peak)?     │
     * │   • Who can manage members — only 'club-admin', or also a 'manager'?        │
     * │ Keys are role names; values are permissions drawn from self::PERMISSIONS.   │
     * └───────────────────────────────────────────────────────────────────────────┘
     *
     * @return array<string, list<string>>
     */
    public static function roleMatrix(): array
    {
        return [
            'club-admin' => self::PERMISSIONS, // full control of the club
            'coach' => ['court.book', 'tournament.manage', 'team.manage'],
            'member' => ['court.book'],
        ];
    }

    /**
     * Create the global permission set. Run once; roles are seeded per-tenant
     * via seedForTenant().
     */
    public function run(): void
    {
        // Permissions carry no team scope — create them in the central (no-team) context.
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    /**
     * Create a club's roles and wire each to its permissions, scoped to that tenant.
     * Roles created while a tenant team id is active are stored against that club only.
     */
    public function seedForTenant(Tenant $tenant): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getTenantKey());

        foreach (self::roleMatrix() as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }
    }
}
