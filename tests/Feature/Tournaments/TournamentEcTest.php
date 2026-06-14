<?php

namespace Tests\Feature\Tournaments;

use App\Domains\Identity\Models\User;
use App\Domains\Membership\Actions\ProvisionClubRoles;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tournaments\Actions\AddManagerToTournament;
use App\Domains\Tournaments\Actions\RemoveManagerFromTournament;
use App\Domains\Tournaments\Events\ManagerAddedToTournament;
use App\Domains\Tournaments\Events\ManagerRemovedFromTournament;
use App\Domains\Tournaments\Exceptions\ManagementException;
use App\Domains\Tournaments\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The EC (executive committee / management) of a tournament — the club members who run
 * THAT tournament. The set can differ from tournament to tournament.
 */
class TournamentEcTest extends TestCase
{
    use RefreshDatabase;

    private function makeClub(string $slug): Tenant
    {
        $club = Tenant::create(['id' => $slug, 'name' => ucfirst($slug).' Club', 'slug' => $slug]);
        $club->domains()->create(['domain' => $slug]);
        app(ProvisionClubRoles::class)->handle($club);

        return $club;
    }

    private function makeMember(Tenant $club, string $role): User
    {
        $user = User::factory()->create();
        $club->users()->attach($user->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($club->getTenantKey());
        $user->assignRole($role);

        return $user;
    }

    private function makeTournament(string $name = 'Open Cup'): Tournament
    {
        return Tournament::create(['name' => $name, 'status' => 'draft', 'format' => 'single_elimination']);
    }

    public function test_a_club_member_can_be_added_to_a_tournaments_management(): void
    {
        Event::fake([ManagerAddedToTournament::class]);
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $tournament = $this->makeTournament();
        app(AddManagerToTournament::class)->handle($tournament, $member);
        tenancy()->end();

        $this->assertDatabaseHas('tournament_management', [
            'tournament_id' => $tournament->id,
            'user_id' => $member->id,
            'tenant_id' => 'alpha',
        ]);
        Event::assertDispatched(ManagerAddedToTournament::class);
    }

    public function test_a_non_member_cannot_be_added_to_management(): void
    {
        $club = $this->makeClub('alpha');
        $outsider = User::factory()->create();

        tenancy()->initialize($club);
        $tournament = $this->makeTournament();

        $this->expectException(ManagementException::class);
        try {
            app(AddManagerToTournament::class)->handle($tournament, $outsider);
        } finally {
            tenancy()->end();
        }
    }

    public function test_the_same_person_cannot_be_added_to_management_twice(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $tournament = $this->makeTournament();
        app(AddManagerToTournament::class)->handle($tournament, $member);

        $this->expectException(ManagementException::class);
        try {
            app(AddManagerToTournament::class)->handle($tournament, $member);
        } finally {
            tenancy()->end();
        }
    }

    public function test_a_manager_can_be_removed_from_management(): void
    {
        Event::fake([ManagerRemovedFromTournament::class]);
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $tournament = $this->makeTournament();
        app(AddManagerToTournament::class)->handle($tournament, $member);
        app(RemoveManagerFromTournament::class)->handle($tournament, $member);
        tenancy()->end();

        $this->assertDatabaseMissing('tournament_management', [
            'tournament_id' => $tournament->id,
            'user_id' => $member->id,
        ]);
        Event::assertDispatched(ManagerRemovedFromTournament::class);
    }

    public function test_management_can_differ_from_tournament_to_tournament(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $spring = $this->makeTournament('Spring');
        $summer = $this->makeTournament('Summer');
        app(AddManagerToTournament::class)->handle($spring, $member);
        tenancy()->end();

        // The member runs Spring but NOT Summer.
        $this->assertDatabaseHas('tournament_management', ['tournament_id' => $spring->id, 'user_id' => $member->id]);
        $this->assertDatabaseMissing('tournament_management', ['tournament_id' => $summer->id, 'user_id' => $member->id]);
    }

    public function test_a_club_admin_can_add_management_over_http(): void
    {
        $club = $this->makeClub('alpha');
        $admin = $this->makeMember($club, 'club-admin');
        $member = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $tournament = $this->makeTournament();
        tenancy()->end();

        $this->withoutVite()->actingAs($admin)
            ->from("http://alpha.localhost/tournaments/{$tournament->id}")
            ->post("http://alpha.localhost/tournaments/{$tournament->id}/management", ['user_id' => $member->id])
            ->assertRedirect();

        $this->assertDatabaseHas('tournament_management', ['tournament_id' => $tournament->id, 'user_id' => $member->id]);
    }

    public function test_a_member_without_tournament_manage_cannot_add_management(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member');
        $other = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $tournament = $this->makeTournament();
        tenancy()->end();

        $this->withoutVite()->actingAs($member)
            ->post("http://alpha.localhost/tournaments/{$tournament->id}/management", ['user_id' => $other->id])
            ->assertForbidden();
    }

    public function test_adding_a_non_member_to_management_over_http_returns_a_422(): void
    {
        $club = $this->makeClub('alpha');
        $admin = $this->makeMember($club, 'club-admin');
        $outsider = User::factory()->create();

        tenancy()->initialize($club);
        $tournament = $this->makeTournament();
        tenancy()->end();

        $this->withoutVite()->actingAs($admin)
            ->from("http://alpha.localhost/tournaments/{$tournament->id}")
            ->post("http://alpha.localhost/tournaments/{$tournament->id}/management", ['user_id' => $outsider->id])
            ->assertSessionHasErrors('user_id');
    }
}
