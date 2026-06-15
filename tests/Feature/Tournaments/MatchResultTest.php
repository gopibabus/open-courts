<?php

namespace Tests\Feature\Tournaments;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tournaments\Enums\CategoryType;
use App\Domains\Tournaments\Events\MatchRecorded;
use App\Domains\Tournaments\Models\Tournament;
use App\Domains\Tournaments\Models\TournamentCategory;
use App\Domains\Tournaments\Models\TournamentMatch;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MatchResultTest extends TestCase
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

    private function makeMember(Tenant $club, string $role): User
    {
        $user = User::factory()->create();
        $club->users()->attach($user->id);

        app(PermissionRegistrar::class)->setPermissionsTeamId($club->getTenantKey());
        $user->assignRole($role);

        return $user;
    }

    /** @return array{0: Tournament, 1: TournamentCategory} */
    private function makeTournament(Tenant $club): array
    {
        tenancy()->initialize($club);
        $tournament = Tournament::create(['name' => 'Summer Cup', 'status' => 'open', 'format' => 'single_elimination']);
        $category = TournamentCategory::create([
            'tournament_id' => $tournament->id,
            'name' => "Men's Singles",
            'type' => CategoryType::Singles,
        ]);
        tenancy()->end();

        return [$tournament, $category];
    }

    public function test_an_admin_can_record_a_match_result(): void
    {
        Event::fake([MatchRecorded::class]);

        $club = $this->makeClub('alpha');
        $admin = $this->makeMember($club, 'club-admin');
        $winner = $this->makeMember($club, 'member');
        $loser = $this->makeMember($club, 'member');
        [$tournament, $category] = $this->makeTournament($club);

        $this->actingAs($admin)
            ->post("http://alpha.localhost/tournaments/{$tournament->id}/matches", [
                'category_id' => $category->id,
                'round' => 'final',
                'player_one_id' => $winner->id,
                'player_two_id' => $loser->id,
                'winner_id' => $winner->id,
                'score' => '6-4 6-2',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tournament_matches', [
            'tenant_id' => $club->id,
            'tournament_id' => $tournament->id,
            'category_id' => $category->id,
            'round' => 'final',
            'winner_id' => $winner->id,
            'score' => '6-4 6-2',
        ]);

        Event::assertDispatched(MatchRecorded::class);
    }

    public function test_the_winner_must_be_one_of_the_two_players(): void
    {
        $club = $this->makeClub('alpha');
        $admin = $this->makeMember($club, 'club-admin');
        $p1 = $this->makeMember($club, 'member');
        $p2 = $this->makeMember($club, 'member');
        $bystander = $this->makeMember($club, 'member');
        [$tournament, $category] = $this->makeTournament($club);

        $this->actingAs($admin)
            ->post("http://alpha.localhost/tournaments/{$tournament->id}/matches", [
                'category_id' => $category->id,
                'round' => 'final',
                'player_one_id' => $p1->id,
                'player_two_id' => $p2->id,
                'winner_id' => $bystander->id,
            ])
            ->assertSessionHasErrors('winner_id');
    }

    public function test_a_match_needs_two_different_players(): void
    {
        $club = $this->makeClub('alpha');
        $admin = $this->makeMember($club, 'club-admin');
        $p1 = $this->makeMember($club, 'member');
        [$tournament, $category] = $this->makeTournament($club);

        $this->actingAs($admin)
            ->post("http://alpha.localhost/tournaments/{$tournament->id}/matches", [
                'category_id' => $category->id,
                'round' => 'final',
                'player_one_id' => $p1->id,
                'player_two_id' => $p1->id,
                'winner_id' => $p1->id,
            ])
            ->assertSessionHasErrors('player_one_id');
    }

    public function test_recording_a_result_requires_the_manage_permission(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member'); // no tournament.manage
        $other = $this->makeMember($club, 'member');
        [$tournament, $category] = $this->makeTournament($club);

        $this->actingAs($member)
            ->post("http://alpha.localhost/tournaments/{$tournament->id}/matches", [
                'category_id' => $category->id,
                'round' => 'final',
                'player_one_id' => $member->id,
                'player_two_id' => $other->id,
                'winner_id' => $member->id,
            ])
            ->assertForbidden();
    }

    public function test_match_results_are_isolated_between_clubs(): void
    {
        $alpha = $this->makeClub('alpha');
        $beta = $this->makeClub('beta');
        $a1 = $this->makeMember($alpha, 'member');
        $a2 = $this->makeMember($alpha, 'member');
        [$tournament, $category] = $this->makeTournament($alpha);

        tenancy()->initialize($alpha);
        TournamentMatch::create([
            'tournament_id' => $tournament->id,
            'category_id' => $category->id,
            'round' => 'final',
            'player_one_id' => $a1->id,
            'player_two_id' => $a2->id,
            'winner_id' => $a1->id,
        ]);
        tenancy()->end();

        tenancy()->initialize($beta);
        $this->assertSame(0, TournamentMatch::count(), 'beta must not see alpha match results');
        tenancy()->end();
    }
}
