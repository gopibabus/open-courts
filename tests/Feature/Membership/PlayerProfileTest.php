<?php

namespace Tests\Feature\Membership;

use App\Domains\Identity\Models\User;
use App\Domains\Membership\Actions\BuildPlayerProfile;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tournaments\Enums\CategoryType;
use App\Domains\Tournaments\Models\Tournament;
use App\Domains\Tournaments\Models\TournamentCategory;
use App\Domains\Tournaments\Models\TournamentMatch;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlayerProfileTest extends TestCase
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

    private function recordMatch(Tenant $club, Tournament $t, TournamentCategory $c, string $round, User $winner, User $loser): void
    {
        tenancy()->initialize($club);
        TournamentMatch::create([
            'tournament_id' => $t->id,
            'category_id' => $c->id,
            'round' => $round,
            'player_one_id' => $winner->id,
            'player_two_id' => $loser->id,
            'winner_id' => $winner->id,
        ]);
        tenancy()->end();
    }

    /** @return array{0: Tenant, 1: array<string, User>, 2: Tournament, 3: TournamentCategory} */
    private function seedFinishedDraw(): array
    {
        $club = $this->makeClub('alpha');
        $players = [
            'ben' => $this->makeMember($club, 'member'),
            'coach' => $this->makeMember($club, 'coach'),
            'omar' => $this->makeMember($club, 'member'),
            'owner' => $this->makeMember($club, 'club-admin'),
        ];
        [$t, $c] = $this->makeTournament($club);

        // Ben beats Omar (semi), Coach beats Owner (semi), Ben beats Coach (final).
        $this->recordMatch($club, $t, $c, 'semi_final', $players['ben'], $players['omar']);
        $this->recordMatch($club, $t, $c, 'semi_final', $players['coach'], $players['owner']);
        $this->recordMatch($club, $t, $c, 'final', $players['ben'], $players['coach']);

        return [$club, $players, $t, $c];
    }

    private function profileFor(Tenant $club, User $member): array
    {
        tenancy()->initialize($club);
        $profile = app(BuildPlayerProfile::class)->handle($club, $member);
        tenancy()->end();

        return $profile;
    }

    public function test_the_finals_winner_gets_a_title_and_a_champion_trophy(): void
    {
        [$club, $players] = $this->seedFinishedDraw();

        $profile = $this->profileFor($club, $players['ben']);

        $this->assertSame(2, $profile['record']['played']);
        $this->assertSame(2, $profile['record']['won']);
        $this->assertSame(0, $profile['record']['lost']);
        $this->assertSame(1, $profile['record']['titles']);
        $this->assertSame(100, $profile['record']['winPct']);

        $this->assertSame('champion', $profile['trophies'][0]['placement']);

        $badgeKeys = array_column($profile['badges'], 'key');
        $this->assertContains('first_win', $badgeKeys);
        $this->assertContains('first_title', $badgeKeys);
    }

    public function test_the_finals_loser_is_a_runner_up(): void
    {
        [$club, $players] = $this->seedFinishedDraw();

        $profile = $this->profileFor($club, $players['coach']);

        $this->assertSame(1, $profile['record']['won']);
        $this->assertSame(1, $profile['record']['lost']);
        $this->assertSame(0, $profile['record']['titles']);
        $this->assertSame('runner_up', $profile['trophies'][0]['placement']);
    }

    public function test_a_beaten_semi_finalist_gets_a_semi_finalist_placement(): void
    {
        [$club, $players] = $this->seedFinishedDraw();

        $profile = $this->profileFor($club, $players['omar']);

        $this->assertSame(0, $profile['record']['won']);
        $this->assertSame(1, $profile['record']['lost']);
        $this->assertSame('semi_finalist', $profile['trophies'][0]['placement']);
    }

    public function test_the_profile_page_renders_with_the_derived_record(): void
    {
        [$club, $players] = $this->seedFinishedDraw();
        $viewer = $players['owner'];
        $ben = $players['ben'];

        $this->actingAs($viewer)
            ->withoutVite()
            ->get("http://alpha.localhost/members/{$ben->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('membership/members/show')
                ->where('profile.name', $ben->name)
                ->where('profile.record.titles', 1)
                ->where('profile.trophies.0.placement', 'champion'));
    }
}
