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
use App\Domains\Tournaments\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TeamManagementTest extends TestCase
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

    private function makeOutsider(): User
    {
        return User::factory()->create();
    }

    /** Create a tournament in the current tenant context. */
    private function makeTournament(string $name = 'Open Cup'): Tournament
    {
        return Tournament::create(['name' => $name, 'status' => 'draft', 'format' => 'single_elimination']);
    }

    private function makeTeam(Tournament $tournament, string $name): Team
    {
        return app(CreateTeam::class)->handle(new CreateTeamData(name: $name, tournamentId: $tournament->id));
    }

    public function test_a_manager_can_create_a_team_belonging_to_a_tournament(): void
    {
        Event::fake([TeamCreated::class]);
        $club = $this->makeClub('alpha');

        tenancy()->initialize($club);
        $tournament = $this->makeTournament();
        $team = $this->makeTeam($tournament, 'First VII');
        tenancy()->end();

        $this->assertSame('alpha', $team->tenant_id);
        $this->assertSame('First VII', $team->name);
        $this->assertSame($tournament->id, $team->tournament_id);

        Event::assertDispatched(TeamCreated::class, fn (TeamCreated $e) => $e->team->is($team));
    }

    public function test_a_club_admin_can_create_a_team_under_a_tournament_over_http(): void
    {
        $club = $this->makeClub('alpha');
        $admin = $this->makeMember($club, 'club-admin');

        tenancy()->initialize($club);
        $tournament = $this->makeTournament();
        tenancy()->end();

        $this->withoutVite()->actingAs($admin)
            ->post("http://alpha.localhost/tournaments/{$tournament->id}/teams", ['name' => 'Saturday Squad'])
            ->assertRedirect();

        $this->assertDatabaseHas('teams', ['name' => 'Saturday Squad', 'tenant_id' => 'alpha', 'tournament_id' => $tournament->id]);
    }

    public function test_a_coach_can_create_a_team_under_a_tournament_over_http(): void
    {
        $club = $this->makeClub('alpha');
        $coach = $this->makeMember($club, 'coach');

        tenancy()->initialize($club);
        $tournament = $this->makeTournament();
        tenancy()->end();

        $this->withoutVite()->actingAs($coach)
            ->post("http://alpha.localhost/tournaments/{$tournament->id}/teams", ['name' => 'Coaching Squad'])
            ->assertRedirect();

        $this->assertDatabaseHas('teams', ['name' => 'Coaching Squad', 'tenant_id' => 'alpha']);
    }

    public function test_a_club_member_can_be_added_with_tenant_and_tournament_id_on_the_pivot(): void
    {
        Event::fake([PlayerAddedToTeam::class]);
        $club = $this->makeClub('alpha');
        $player = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $tournament = $this->makeTournament();
        $team = $this->makeTeam($tournament, 'First VII');
        app(AddPlayerToTeam::class)->handle($team, $player);
        tenancy()->end();

        $this->assertDatabaseHas('team_player', [
            'team_id' => $team->id,
            'user_id' => $player->id,
            'tenant_id' => 'alpha',
            'tournament_id' => $tournament->id,
        ]);

        Event::assertDispatched(PlayerAddedToTeam::class);
    }

    public function test_a_non_member_cannot_be_added_to_the_roster(): void
    {
        $club = $this->makeClub('alpha');
        $outsider = $this->makeOutsider();

        tenancy()->initialize($club);
        $team = $this->makeTeam($this->makeTournament(), 'First VII');

        $this->expectException(RosterException::class);
        try {
            app(AddPlayerToTeam::class)->handle($team, $outsider);
        } finally {
            tenancy()->end();
        }
    }

    public function test_the_same_player_cannot_be_added_to_the_same_team_twice(): void
    {
        $club = $this->makeClub('alpha');
        $player = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $team = $this->makeTeam($this->makeTournament(), 'First VII');
        app(AddPlayerToTeam::class)->handle($team, $player);

        $this->expectException(RosterException::class);
        try {
            app(AddPlayerToTeam::class)->handle($team, $player);
        } finally {
            tenancy()->end();
        }
    }

    public function test_a_member_can_be_on_only_one_team_per_tournament(): void
    {
        $club = $this->makeClub('alpha');
        $player = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $tournament = $this->makeTournament();
        $teamA = $this->makeTeam($tournament, 'Team A');
        $teamB = $this->makeTeam($tournament, 'Team B');
        app(AddPlayerToTeam::class)->handle($teamA, $player);

        $this->expectException(RosterException::class);
        try {
            app(AddPlayerToTeam::class)->handle($teamB, $player); // same tournament — rejected
        } finally {
            tenancy()->end();
        }
    }

    public function test_a_member_can_play_for_teams_in_different_tournaments(): void
    {
        $club = $this->makeClub('alpha');
        $player = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $spring = $this->makeTeam($this->makeTournament('Spring'), 'Spring Team');
        $summer = $this->makeTeam($this->makeTournament('Summer'), 'Summer Team');
        app(AddPlayerToTeam::class)->handle($spring, $player);
        app(AddPlayerToTeam::class)->handle($summer, $player); // different tournament — allowed
        tenancy()->end();

        $this->assertDatabaseHas('team_player', ['team_id' => $spring->id, 'user_id' => $player->id]);
        $this->assertDatabaseHas('team_player', ['team_id' => $summer->id, 'user_id' => $player->id]);
    }

    public function test_adding_to_a_second_team_in_the_same_tournament_over_http_returns_a_422(): void
    {
        $club = $this->makeClub('alpha');
        $admin = $this->makeMember($club, 'club-admin');
        $player = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $tournament = $this->makeTournament();
        $teamA = $this->makeTeam($tournament, 'Team A');
        $teamB = $this->makeTeam($tournament, 'Team B');
        app(AddPlayerToTeam::class)->handle($teamA, $player);
        tenancy()->end();

        $this->withoutVite()->actingAs($admin)
            ->from("http://alpha.localhost/teams/{$teamB->id}")
            ->post("http://alpha.localhost/teams/{$teamB->id}/players", ['user_id' => $player->id])
            ->assertSessionHasErrors('user_id');
    }

    public function test_a_player_can_be_removed_from_the_roster(): void
    {
        Event::fake([PlayerRemovedFromTeam::class]);
        $club = $this->makeClub('alpha');
        $player = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $team = $this->makeTeam($this->makeTournament(), 'First VII');
        app(AddPlayerToTeam::class)->handle($team, $player);
        app(RemovePlayerFromTeam::class)->handle($team, $player);
        tenancy()->end();

        $this->assertDatabaseMissing('team_player', ['team_id' => $team->id, 'user_id' => $player->id]);
        Event::assertDispatched(PlayerRemovedFromTeam::class);
    }

    public function test_a_member_without_team_manage_cannot_create_a_team(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $tournament = $this->makeTournament();
        tenancy()->end();

        $this->withoutVite()->actingAs($member)
            ->post("http://alpha.localhost/tournaments/{$tournament->id}/teams", ['name' => 'Sneaky Squad'])
            ->assertForbidden();

        $this->assertDatabaseMissing('teams', ['name' => 'Sneaky Squad']);
    }

    public function test_teams_are_isolated_between_clubs(): void
    {
        $alpha = $this->makeClub('alpha');
        $beta = $this->makeClub('beta');

        tenancy()->initialize($alpha);
        $this->makeTeam($this->makeTournament(), 'Alpha Squad');
        tenancy()->end();

        tenancy()->initialize($beta);
        $this->makeTeam($this->makeTournament(), 'Beta Squad');
        $this->assertSame(1, Team::count());
        $this->assertSame('Beta Squad', Team::first()->name);
        tenancy()->end();

        tenancy()->initialize($alpha);
        $this->assertSame(1, Team::count());
        $this->assertSame('Alpha Squad', Team::first()->name);
        tenancy()->end();
    }
}
