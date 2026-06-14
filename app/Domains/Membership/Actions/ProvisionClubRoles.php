<?php

declare(strict_types=1);

namespace App\Domains\Membership\Actions;

use App\Domains\Tenancy\Models\Tenant;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Ensure the global permission set exists and create a club's roles, scoped to that
 * club's team context. Idempotent — safe to call on every provisioning.
 *
 * The role matrix is owned by RolePermissionSeeder::roleMatrix() (a single source of
 * truth shared with the seeders).
 */
final class ProvisionClubRoles
{
    public function handle(Tenant $club): void
    {
        $registrar = app(PermissionRegistrar::class);

        // Permissions are global (no team scope).
        $registrar->setPermissionsTeamId(null);
        foreach (RolePermissionSeeder::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Roles are scoped to this club.
        $registrar->setPermissionsTeamId($club->getTenantKey());
        foreach (RolePermissionSeeder::roleMatrix() as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }
    }
}
