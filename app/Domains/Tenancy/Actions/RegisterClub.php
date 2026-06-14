<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Membership\Actions\ProvisionClubRoles;
use App\Domains\Tenancy\Data\RegisterClubData;
use App\Domains\Tenancy\Events\ClubRegistered;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * Provision a new club: create the owner + tenant + subdomain, wire up the club's
 * roles, and make the owner a club-admin — all in one transaction. The ClubRegistered
 * event fires after commit.
 */
final class RegisterClub
{
    public function __construct(private readonly ProvisionClubRoles $provisionClubRoles) {}

    public function handle(RegisterClubData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $owner = User::create([
                'name' => $data->ownerName,
                'email' => $data->ownerEmail,
                'password' => Hash::make($data->password),
            ]);

            // Tenant id is an auto-generated UUID (stancl); slug is the subdomain label.
            $club = Tenant::create([
                'name' => $data->clubName,
                'slug' => $data->slug,
            ]);
            $club->domains()->create(['domain' => $data->slug]);
            $club->users()->attach($owner->id);

            $this->provisionClubRoles->handle($club);

            app(PermissionRegistrar::class)->setPermissionsTeamId($club->getTenantKey());
            $owner->assignRole('club-admin');

            ClubRegistered::dispatch($club, $owner);

            return $owner;
        });
    }
}
