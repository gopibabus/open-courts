<?php

namespace Tests\Feature\Tournaments;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tournaments\Actions\BuildStandings;
use App\Domains\Tournaments\Actions\GenerateRoundRobin;
use App\Domains\Tournaments\Actions\UpdateMatchResult;
use App\Domains\Tournaments\Enums\CategoryType;
use App\Domains\Tournaments\Enums\RegistrationStatus;
use App\Domains\Tournaments\Enums\TournamentFormat;
use App\Domains\Tournaments\Models\Registration;
use App\Domains\Tournaments\Models\Tournament;
use App\Domains\Tournaments\Models\TournamentCategory;
use App\Domains\Tournaments\Models\TournamentMatch;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoundRobinTest extends TestCase
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

    /** @return array{0: TournamentCategory, 1: array<int, User>} */
    private function makeRoundRobinCategory(Tenant $club, int $count): array
    {
        $members = [];
        for ($i = 0; $i < $count; $i++) {
            $members[] = $this->makeMember($club, 'member');
        }

        tenancy()->initialize($club);
        $tournament = Tournament::create(['name' => 'League', 'status' => 'open', 'format' => 'round_robin']);
        $category = TournamentCategory::create([
            'tournament_id' => $tournament->id,
            'name' => 'Division 1',
            'type' => CategoryType::Singles,
            'format' => TournamentFormat::RoundRobin,
        ]);
        foreach ($members as $member) {
            Registration::create([
                'tournament_id' => $tournament->id,
                'category_id' => $category->id,
                'user_id' => $member->id,
                'status' => RegistrationStatus::Confirmed,
            ]);
        }
        tenancy()->end();

        return [$category, $members];
    }

    public function test_round_robin_generates_every_pairing(): void
    {
        $club = $this->makeClub('alpha');
        [$category] = $this->makeRoundRobinCategory($club, 4);

        tenancy()->initialize($club);
        app(GenerateRoundRobin::class)->handle($category);

        $matches = TournamentMatch::where('category_id', $category->id)->get();
        $this->assertCount(6, $matches, '4 entrants → C(4,2) = 6 fixtures');
        $this->assertTrue($matches->every(fn (TournamentMatch $m) => $m->round->value === 'group'));
        $this->assertTrue($matches->every(fn (TournamentMatch $m) => $m->next_match_id === null), 'round-robin has no advancement');
        tenancy()->end();
    }

    public function test_standings_rank_by_wins(): void
    {
        $club = $this->makeClub('alpha');
        [$category, $members] = $this->makeRoundRobinCategory($club, 3);
        [$a, $b, $c] = $members;

        tenancy()->initialize($club);
        app(GenerateRoundRobin::class)->handle($category);
        $record = app(UpdateMatchResult::class);

        // A beats everyone, B beats C → A: 2-0, B: 1-1, C: 0-2.
        foreach (TournamentMatch::where('category_id', $category->id)->get() as $m) {
            $pair = [$m->player_one_id, $m->player_two_id];
            $winner = in_array($a->id, $pair, true) ? $a->id : $b->id;
            $record->handle($m, $winner, '6-2 6-2', null);
        }

        $standings = app(BuildStandings::class)->handle($category);
        tenancy()->end();

        $this->assertSame($a->id, $standings[0]['userId']);
        $this->assertSame(2, $standings[0]['won']);
        $this->assertSame($b->id, $standings[1]['userId']);
        $this->assertSame(1, $standings[1]['won']);
        $this->assertSame($c->id, $standings[2]['userId']);
        $this->assertSame(0, $standings[2]['won']);
    }

    public function test_generate_endpoint_builds_a_round_robin_for_a_round_robin_category(): void
    {
        $club = $this->makeClub('alpha');
        [$category] = $this->makeRoundRobinCategory($club, 4);
        $admin = $this->makeMember($club, 'club-admin');

        $this->actingAs($admin)
            ->post("http://alpha.localhost/categories/{$category->id}/bracket")
            ->assertRedirect();

        tenancy()->initialize($club);
        $matches = TournamentMatch::where('category_id', $category->id)->get();
        $this->assertCount(6, $matches);
        $this->assertTrue($matches->every(fn (TournamentMatch $m) => $m->round->value === 'group'));
        tenancy()->end();
    }
}
