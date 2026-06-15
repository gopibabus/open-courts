<?php

namespace Tests\Feature\Tournaments;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tournaments\Actions\GenerateBracket;
use App\Domains\Tournaments\Actions\SeedEntrants;
use App\Domains\Tournaments\Actions\UpdateMatchResult;
use App\Domains\Tournaments\Enums\CategoryType;
use App\Domains\Tournaments\Enums\RegistrationStatus;
use App\Domains\Tournaments\Models\Registration;
use App\Domains\Tournaments\Models\Team;
use App\Domains\Tournaments\Models\Tournament;
use App\Domains\Tournaments\Models\TournamentCategory;
use App\Domains\Tournaments\Models\TournamentMatch;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BracketTest extends TestCase
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

    /** @return array{0: Tournament, 1: TournamentCategory, 2: array<int, User>} */
    private function makeCategoryWithEntrants(Tenant $club, int $count): array
    {
        $members = [];
        for ($i = 0; $i < $count; $i++) {
            $members[] = $this->makeMember($club, 'member');
        }

        tenancy()->initialize($club);
        $tournament = Tournament::create(['name' => 'Cup', 'status' => 'open', 'format' => 'single_elimination']);
        $category = TournamentCategory::create(['tournament_id' => $tournament->id, 'name' => "Men's Singles", 'type' => CategoryType::Singles]);
        foreach ($members as $member) {
            Registration::create([
                'tournament_id' => $tournament->id,
                'category_id' => $category->id,
                'user_id' => $member->id,
                'status' => RegistrationStatus::Confirmed,
            ]);
        }
        tenancy()->end();

        return [$tournament, $category, $members];
    }

    public function test_generating_a_bracket_creates_the_full_draw(): void
    {
        $club = $this->makeClub('alpha');
        [, $category] = $this->makeCategoryWithEntrants($club, 8);

        tenancy()->initialize($club);
        app(GenerateBracket::class)->handle($category);

        $matches = TournamentMatch::where('category_id', $category->id)->get();
        $this->assertCount(7, $matches, '8-player draw = 4 QF + 2 SF + 1 final');
        $this->assertSame(4, $matches->where('round', 'quarter_final')->count());
        $this->assertSame(2, $matches->where('round', 'semi_final')->count());
        $this->assertSame(1, $matches->where('round', 'final')->count());

        // A quarter-final advances into a semi-final.
        $qf = $matches->firstWhere('round', 'quarter_final');
        $this->assertNotNull($qf->next_match_id);
        $this->assertSame('semi_final', TournamentMatch::find($qf->next_match_id)->round->value);

        // Round-1 (quarter-finals) carry the seeded entrants.
        $this->assertNotNull($qf->player_one_id);
        tenancy()->end();
    }

    public function test_byes_auto_advance_a_lone_player(): void
    {
        $club = $this->makeClub('alpha');
        [, $category] = $this->makeCategoryWithEntrants($club, 5); // padded to 8 → 3 byes

        tenancy()->initialize($club);
        app(GenerateBracket::class)->handle($category);

        // At least one first-round match is already completed (a bye) with its player advanced.
        $bye = TournamentMatch::where('category_id', $category->id)
            ->where('round', 'quarter_final')
            ->where('status', 'completed')
            ->first();

        $this->assertNotNull($bye, 'a bye should auto-complete');
        $this->assertNotNull($bye->winner_id);
        $next = TournamentMatch::find($bye->next_match_id);
        $this->assertTrue(
            $next->player_one_id === $bye->winner_id || $next->player_two_id === $bye->winner_id,
            'the bye winner is advanced into the next match',
        );
        tenancy()->end();
    }

    public function test_recording_a_result_advances_the_winner(): void
    {
        $club = $this->makeClub('alpha');
        [, $category] = $this->makeCategoryWithEntrants($club, 4); // 2 semis + final

        tenancy()->initialize($club);
        app(GenerateBracket::class)->handle($category);

        $semi = TournamentMatch::where('category_id', $category->id)->where('round', 'semi_final')->where('position', 0)->first();
        $winnerId = (int) $semi->player_one_id;

        app(UpdateMatchResult::class)->handle($semi, $winnerId, '6-4 6-2', 'Tight match');

        $semi->refresh();
        $this->assertSame('completed', $semi->status);
        $this->assertSame($winnerId, $semi->winner_id);

        $final = TournamentMatch::where('category_id', $category->id)->where('round', 'final')->first();
        $slot = $semi->next_slot === 1 ? $final->player_one_id : $final->player_two_id;
        $this->assertSame($winnerId, $slot, 'the winner advances into the final');
        tenancy()->end();
    }

    public function test_generating_requires_the_manage_permission(): void
    {
        $club = $this->makeClub('alpha');
        [, $category] = $this->makeCategoryWithEntrants($club, 4);
        $member = $this->makeMember($club, 'member'); // no tournament.manage

        $this->actingAs($member)
            ->post("http://alpha.localhost/categories/{$category->id}/bracket")
            ->assertForbidden();
    }

    public function test_an_admin_can_generate_then_view_the_bracket(): void
    {
        $club = $this->makeClub('alpha');
        [, $category] = $this->makeCategoryWithEntrants($club, 4);
        $admin = $this->makeMember($club, 'club-admin');

        $this->actingAs($admin)
            ->post("http://alpha.localhost/categories/{$category->id}/bracket")
            ->assertRedirect();

        tenancy()->initialize($club);
        $this->assertSame(3, TournamentMatch::where('category_id', $category->id)->count());
        tenancy()->end();

        $this->actingAs($admin)
            ->withoutVite()
            ->get("http://alpha.localhost/categories/{$category->id}/bracket")
            ->assertOk();
    }

    public function test_manual_seeding_orders_the_draw(): void
    {
        $club = $this->makeClub('alpha');
        [, $category, $members] = $this->makeCategoryWithEntrants($club, 4);

        tenancy()->initialize($club);
        $registrationIds = Registration::where('category_id', $category->id)->orderBy('id')->pluck('id')->all();
        // Reverse the order → the last-registered entrant becomes the top seed.
        app(SeedEntrants::class)->handle($category, array_reverse($registrationIds));
        app(GenerateBracket::class)->handle($category);

        $semiZero = TournamentMatch::where('category_id', $category->id)->where('round', 'semi_final')->where('position', 0)->first();
        tenancy()->end();

        // The top seed lands in the first slot of the draw.
        $this->assertSame(end($members)->id, $semiZero->player_one_id);
    }

    public function test_the_seeding_endpoint_persists_order_and_is_gated(): void
    {
        $club = $this->makeClub('alpha');
        [, $category] = $this->makeCategoryWithEntrants($club, 3);
        $admin = $this->makeMember($club, 'club-admin');
        $member = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $registrationIds = Registration::where('category_id', $category->id)->orderBy('id')->pluck('id')->all();
        tenancy()->end();
        $reversed = array_reverse($registrationIds);

        // A plain member cannot seed.
        $this->actingAs($member)
            ->patch("http://alpha.localhost/categories/{$category->id}/seeding", ['entrants' => $reversed])
            ->assertForbidden();

        // An admin can; seeds are persisted in the given order.
        $this->actingAs($admin)
            ->patch("http://alpha.localhost/categories/{$category->id}/seeding", ['entrants' => $reversed])
            ->assertRedirect();

        $this->assertDatabaseHas('registrations', ['id' => $reversed[0], 'seed' => 1]);
        $this->assertDatabaseHas('registrations', ['id' => $reversed[2], 'seed' => 3]);
    }

    public function test_a_doubles_bracket_shows_each_pair(): void
    {
        $club = $this->makeClub('alpha');
        $a = $this->makeMember($club, 'member');
        $b = $this->makeMember($club, 'member');
        $c = $this->makeMember($club, 'member');
        $d = $this->makeMember($club, 'member');
        $admin = $this->makeMember($club, 'club-admin');

        tenancy()->initialize($club);
        $tournament = Tournament::create(['name' => 'Cup', 'status' => 'open', 'format' => 'single_elimination']);
        $category = TournamentCategory::create(['tournament_id' => $tournament->id, 'name' => 'Mixed', 'type' => CategoryType::Mixed]);
        foreach ([[$a, $b], [$c, $d]] as [$entrant, $partner]) {
            Registration::create([
                'tournament_id' => $tournament->id,
                'category_id' => $category->id,
                'user_id' => $entrant->id,
                'partner_id' => $partner->id,
                'status' => RegistrationStatus::Confirmed,
            ]);
        }
        app(GenerateBracket::class)->handle($category);
        tenancy()->end();

        // The final's two sides each carry their partner's name.
        $this->actingAs($admin)
            ->withoutVite()
            ->get("http://alpha.localhost/categories/{$category->id}/bracket")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('tournaments/bracket')
                ->where('rounds.0.matches.0.playerOne.partner', $b->name)
                ->where('rounds.0.matches.0.playerTwo.partner', $d->name));
    }

    public function test_a_team_bracket_is_drawn_over_the_tournaments_teams(): void
    {
        $club = $this->makeClub('alpha');
        $admin = $this->makeMember($club, 'club-admin');

        tenancy()->initialize($club);
        $tournament = Tournament::create(['name' => 'Cup', 'status' => 'open', 'format' => 'single_elimination']);
        $category = TournamentCategory::create(['tournament_id' => $tournament->id, 'name' => 'Club Teams', 'type' => CategoryType::Singles, 'is_team' => true]);
        $teamA = Team::create(['tournament_id' => $tournament->id, 'name' => 'Alpha A']);
        $teamB = Team::create(['tournament_id' => $tournament->id, 'name' => 'Alpha B']);

        app(GenerateBracket::class)->handle($category);

        $final = TournamentMatch::where('category_id', $category->id)->where('round', 'final')->first();
        $this->assertNotNull($final->team_one_id, 'the final is contested by two teams');
        $this->assertNotNull($final->team_two_id);
        $this->assertNull($final->player_one_id, 'a team match uses team columns, not player columns');

        // Recording a team winner sets winner_team_id (not winner_id).
        app(UpdateMatchResult::class)->handle($final, (int) $teamA->id, '3-1', null);
        $final->refresh();
        $this->assertSame('completed', $final->status);
        $this->assertSame($teamA->id, $final->winner_team_id);
        $this->assertNull($final->winner_id);
        tenancy()->end();

        // The bracket page shows the team names as the sides.
        $this->actingAs($admin)
            ->withoutVite()
            ->get("http://alpha.localhost/categories/{$category->id}/bracket")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('tournaments/bracket')
                ->where('rounds.0.matches.0.playerOne.name', $teamA->name));
    }

    public function test_an_image_can_be_attached_to_a_match(): void
    {
        Storage::fake('public');

        $club = $this->makeClub('alpha');
        [, $category] = $this->makeCategoryWithEntrants($club, 4);
        $admin = $this->makeMember($club, 'club-admin');

        tenancy()->initialize($club);
        app(GenerateBracket::class)->handle($category);
        $match = TournamentMatch::where('category_id', $category->id)->where('round', 'semi_final')->first();
        tenancy()->end();

        $this->actingAs($admin)
            ->post("http://alpha.localhost/matches/{$match->id}/attachments", [
                'image' => UploadedFile::fake()->image('scorecard.jpg'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('match_attachments', [
            'tenant_id' => $club->id,
            'match_id' => $match->id,
        ]);

        // The file was stored on the public disk (path recorded on the row above).
        tenancy()->initialize($club);
        $this->assertNotEmpty($match->attachments()->first()->path);
        tenancy()->end();
    }
}
