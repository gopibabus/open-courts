<?php

namespace Tests\Feature\Tournaments;

use App\Domains\Identity\Models\User;
use App\Domains\Membership\Actions\ProvisionClubRoles;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tournaments\Actions\AddPlayerToTeam;
use App\Domains\Tournaments\Actions\CreateTeam;
use App\Domains\Tournaments\Actions\RemovePlayerFromTeam;
use App\Domains\Tournaments\Data\CreateTeamData;
use App\Domains\Tournaments\Events\PlayerAddedToTeam;
use App\Domains\Tournaments\Events\PlayerRemovedFromTeam;
use App\Domains\Tournaments\Events\TeamCreated;
use App\Domains\Tournaments\Exceptions\RosterException;
use App\Domains\Tournaments\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    /** Provision a club (tenant) with its roles, and return it. */
    private function makeClub(string $slug): Tenant
    {
        $club = Tenant::create([
            'id' => $slug,
            'name' => ucfirst($slug).' Club',
            'slug' => $slug,
        ]);
        $club->domains()->create(['domain' => $slug]);

        app(ProvisionClubRoles::class)->handle($club);

        return $club;
    }

    /** Create a user, make them a member of the club, and assign a club-scoped role. */
    private function makeMember(Tenant $club, string $role): User
    {
        $user = User::factory()->create();
        $club->users()->attach($user->id);

        app(PermissionRegistrar::class)->setPermissionsTeamId($club->getTenantKey());
        $user->assignRole($role);

        return $user;
    }

    /** A user who exists but is NOT attached to any club. */
    private function makeOutsider(): User
    {
        return User::factory()->create();
    }

    public function test_a_manager_can_create_a_tenant_scoped_team(): void
    {
        Event::fake([TeamCreated::class]);

        $club = $this->makeClub('alpha');

        tenancy()->initialize($club);
        $team = app(CreateTeam::class)->handle(new CreateTeamData(name: 'First VII'));
        tenancy()->end();

        $this->assertSame('alpha', $team->tenant_id);
        $this->assertSame('First VII', $team->name);
        $this->assertNull($team->tournament_id);

        Event::assertDispatched(
            TeamCreated::class,
            fn (TeamCreated $e) => $e->team->is($team),
        );
    }

    public function test_a_club_admin_can_create_a_team_over_http(): void
    {
        $club = $this->makeClub('alpha');
        $admin = $this->makeMember($club, 'club-admin');

        $response = $this->withoutVite()
            ->actingAs($admin)
            ->post('http://alpha.localhost/teams', ['name' => 'Saturday Squad']);

        $response->assertRedirect();
        $this->assertDatabaseHas('teams', [
            'name' => 'Saturday Squad',
            'tenant_id' => 'alpha',
        ]);
    }

    public function test_a_coach_can_create_a_team_over_http(): void
    {
        $club = $this->makeClub('alpha');
        $coach = $this->makeMember($club, 'coach');

        $this->withoutVite()
            ->actingAs($coach)
            ->post('http://alpha.localhost/teams', ['name' => 'Coaching Squad'])
            ->assertRedirect();

        $this->assertDatabaseHas('teams', ['name' => 'Coaching Squad', 'tenant_id' => 'alpha']);
    }

    public function test_a_club_member_can_be_added_to_the_roster_with_the_correct_tenant_id(): void
    {
        Event::fake([PlayerAddedToTeam::class]);

        $club = $this->makeClub('alpha');
        $player = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $team = app(CreateTeam::class)->handle(new CreateTeamData(name: 'First VII'));
        app(AddPlayerToTeam::class)->handle($team, $player);
        tenancy()->end();

        // The pivot row carries the tenant_id we passed explicitly on attach().
        $this->assertDatabaseHas('team_player', [
            'team_id' => $team->id,
            'user_id' => $player->id,
            'tenant_id' => 'alpha',
        ]);

        Event::assertDispatched(PlayerAddedToTeam::class);
    }

    public function test_a_non_member_cannot_be_added_to_the_roster(): void
    {
        $club = $this->makeClub('alpha');
        $outsider = $this->makeOutsider();

        tenancy()->initialize($club);
        $team = app(CreateTeam::class)->handle(new CreateTeamData(name: 'First VII'));

        $this->expectException(RosterException::class);
        try {
            app(AddPlayerToTeam::class)->handle($team, $outsider);
        } finally {
            tenancy()->end();
        }
    }

    public function test_the_same_player_cannot_be_added_twice(): void
    {
        $club = $this->makeClub('alpha');
        $player = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $team = app(CreateTeam::class)->handle(new CreateTeamData(name: 'First VII'));
        app(AddPlayerToTeam::class)->handle($team, $player);

        $this->expectException(RosterException::class);
        try {
            app(AddPlayerToTeam::class)->handle($team, $player);
        } finally {
            tenancy()->end();
        }
    }

    public function test_a_player_can_be_removed_from_the_roster(): void
    {
        Event::fake([PlayerRemovedFromTeam::class]);

        $club = $this->makeClub('alpha');
        $player = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $team = app(CreateTeam::class)->handle(new CreateTeamData(name: 'First VII'));
        app(AddPlayerToTeam::class)->handle($team, $player);
        app(RemovePlayerFromTeam::class)->handle($team, $player);
        tenancy()->end();

        $this->assertDatabaseMissing('team_player', [
            'team_id' => $team->id,
            'user_id' => $player->id,
        ]);

        Event::assertDispatched(PlayerRemovedFromTeam::class);
    }

    public function test_a_member_without_team_manage_is_forbidden(): void
    {
        $club = $this->makeClub('alpha');
        // 'member' role lacks team.manage (only club-admin and coach have it).
        $member = $this->makeMember($club, 'member');

        $this->withoutVite()
            ->actingAs($member)
            ->post('http://alpha.localhost/teams', ['name' => 'Sneaky Squad'])
            ->assertForbidden();

        $this->assertDatabaseMissing('teams', ['name' => 'Sneaky Squad']);
    }

    public function test_adding_a_non_member_over_http_returns_a_422(): void
    {
        $club = $this->makeClub('alpha');
        $admin = $this->makeMember($club, 'club-admin');
        $outsider = $this->makeOutsider();

        tenancy()->initialize($club);
        $team = app(CreateTeam::class)->handle(new CreateTeamData(name: 'First VII'));
        tenancy()->end();

        $this->withoutVite()
            ->actingAs($admin)
            ->from('http://alpha.localhost/teams/'.$team->id)
            ->post('http://alpha.localhost/teams/'.$team->id.'/players', ['user_id' => $outsider->id])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseMissing('team_player', [
            'team_id' => $team->id,
            'user_id' => $outsider->id,
        ]);
    }

    public function test_teams_are_isolated_between_clubs(): void
    {
        $alpha = $this->makeClub('alpha');
        $beta = $this->makeClub('beta');

        tenancy()->initialize($alpha);
        app(CreateTeam::class)->handle(new CreateTeamData(name: 'Alpha Squad'));
        tenancy()->end();

        tenancy()->initialize($beta);
        app(CreateTeam::class)->handle(new CreateTeamData(name: 'Beta Squad'));
        $this->assertSame(1, Team::count());
        $this->assertSame('Beta Squad', Team::first()->name);
        tenancy()->end();

        tenancy()->initialize($alpha);
        $this->assertSame(1, Team::count());
        $this->assertSame('Alpha Squad', Team::first()->name);
        tenancy()->end();
    }
}
