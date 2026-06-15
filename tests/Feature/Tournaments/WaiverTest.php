<?php

namespace Tests\Feature\Tournaments;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tournaments\Events\WaiverSigned;
use App\Domains\Tournaments\Models\ClubWaiverTemplate;
use App\Domains\Tournaments\Models\Tournament;
use App\Domains\Tournaments\Models\TournamentWaiver;
use App\Domains\Tournaments\Support\DefaultWaiver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WaiverTest extends TestCase
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

    private function makeMember(Tenant $club, string $role = 'member'): User
    {
        $user = User::factory()->create();
        $club->users()->attach($user->id);

        app(PermissionRegistrar::class)->setPermissionsTeamId($club->getTenantKey());
        $user->assignRole($role);

        return $user;
    }

    private function makeTournament(Tenant $club): Tournament
    {
        tenancy()->initialize($club);
        $tournament = Tournament::create(['name' => 'Summer Cup', 'status' => 'open', 'format' => 'single_elimination']);
        tenancy()->end();

        return $tournament;
    }

    public function test_a_player_can_sign_a_waiver(): void
    {
        Event::fake([WaiverSigned::class]);

        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club);
        $tournament = $this->makeTournament($club);

        $this->actingAs($member)
            ->post("http://alpha.localhost/tournaments/{$tournament->id}/waiver", ['agree' => true, 'signature' => 'Jane Doe'])
            ->assertRedirect();

        $this->assertDatabaseHas('tournament_waivers', [
            'tenant_id' => $club->id,
            'tournament_id' => $tournament->id,
            'user_id' => $member->id,
            'signature' => 'Jane Doe',
        ]);

        Event::assertDispatched(WaiverSigned::class, fn (WaiverSigned $e) => $e->waiver->user_id === $member->id);
    }

    public function test_signing_requires_agreement_and_a_signature(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club);
        $tournament = $this->makeTournament($club);

        $this->actingAs($member)
            ->post("http://alpha.localhost/tournaments/{$tournament->id}/waiver", ['agree' => false, 'signature' => ''])
            ->assertSessionHasErrors(['agree', 'signature']);
    }

    public function test_re_signing_updates_the_same_waiver(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club);
        $tournament = $this->makeTournament($club);

        $this->actingAs($member)->post("http://alpha.localhost/tournaments/{$tournament->id}/waiver", ['agree' => true, 'signature' => 'First Name']);
        $this->actingAs($member)->post("http://alpha.localhost/tournaments/{$tournament->id}/waiver", ['agree' => true, 'signature' => 'Updated Name']);

        tenancy()->initialize($club);
        $this->assertSame(1, TournamentWaiver::where('tournament_id', $tournament->id)->where('user_id', $member->id)->count());
        $this->assertSame('Updated Name', TournamentWaiver::where('user_id', $member->id)->first()->signature);
        tenancy()->end();
    }

    public function test_signing_snapshots_the_resolved_clauses(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club);
        $tournament = $this->makeTournament($club);

        $this->actingAs($member)->post("http://alpha.localhost/tournaments/{$tournament->id}/waiver", [
            'agree' => true, 'signature' => 'Jane Doe',
        ]);

        tenancy()->initialize($club);
        $waiver = TournamentWaiver::where('user_id', $member->id)->first();
        // No custom template -> defaults, with {tournament} resolved to the tournament name.
        $this->assertSame(DefaultWaiver::resolve(DefaultWaiver::clauses(), 'Summer Cup'), $waiver->signed_clauses);
        $this->assertStringContainsString('Summer Cup', $waiver->signed_clauses[0]);
        tenancy()->end();
    }

    public function test_resigning_after_a_template_change_updates_the_snapshot(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club);
        $tournament = $this->makeTournament($club);

        $this->actingAs($member)->post("http://alpha.localhost/tournaments/{$tournament->id}/waiver", [
            'agree' => true, 'signature' => 'Jane Doe',
        ]);

        // The organiser edits the template; the already-signed snapshot must NOT change.
        tenancy()->initialize($club);
        ClubWaiverTemplate::create(['clauses' => ['Brand new clause for {tournament}.']]);
        $before = TournamentWaiver::where('user_id', $member->id)->first()->signed_clauses;
        $this->assertNotSame(['Brand new clause for Summer Cup.'], $before);
        tenancy()->end();

        // Re-signing adopts the current template.
        $this->actingAs($member)->post("http://alpha.localhost/tournaments/{$tournament->id}/waiver", [
            'agree' => true, 'signature' => 'Jane Doe',
        ]);

        tenancy()->initialize($club);
        $this->assertSame(['Brand new clause for Summer Cup.'], TournamentWaiver::where('user_id', $member->id)->first()->signed_clauses);
        tenancy()->end();
    }

    public function test_the_waiver_page_freezes_the_snapshot_for_a_signed_player(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club);
        $tournament = $this->makeTournament($club);

        // Sign against the current (default) template.
        $this->actingAs($member)->post("http://alpha.localhost/tournaments/{$tournament->id}/waiver", [
            'agree' => true, 'signature' => 'Jane Doe',
        ]);

        $snapshot = DefaultWaiver::resolve(DefaultWaiver::clauses(), 'Summer Cup');

        // The waiver page renders exactly what they signed.
        $this->withoutVite()->actingAs($member)
            ->get("http://alpha.localhost/tournaments/{$tournament->id}/waiver")
            ->assertInertia(fn (Assert $page) => $page
                ->component('tournaments/waiver')
                ->where('waiverText', $snapshot)
                ->where('signed.signature', 'Jane Doe'));

        // Editing the club template afterwards must NOT change what the signed player sees.
        tenancy()->initialize($club);
        ClubWaiverTemplate::create(['clauses' => ['Totally different clause for {tournament}.']]);
        tenancy()->end();

        $this->withoutVite()->actingAs($member)
            ->get("http://alpha.localhost/tournaments/{$tournament->id}/waiver")
            ->assertInertia(fn (Assert $page) => $page->where('waiverText', $snapshot));
    }

    public function test_the_waiver_page_shows_the_live_resolved_template_for_an_unsigned_player(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club);
        $tournament = $this->makeTournament($club);

        tenancy()->initialize($club);
        ClubWaiverTemplate::create(['clauses' => ['Play safe at {tournament}.']]);
        tenancy()->end();

        // {tournament} is resolved at display time; an unsigned player sees the live template.
        $this->withoutVite()->actingAs($member)
            ->get("http://alpha.localhost/tournaments/{$tournament->id}/waiver")
            ->assertInertia(fn (Assert $page) => $page
                ->component('tournaments/waiver')
                ->where('waiverText', ['Play safe at Summer Cup.'])
                ->where('signed', null));
    }

    public function test_waivers_are_isolated_between_clubs(): void
    {
        $alpha = $this->makeClub('alpha');
        $beta = $this->makeClub('beta');
        $member = $this->makeMember($alpha);
        $tournament = $this->makeTournament($alpha);

        $this->actingAs($member)->post("http://alpha.localhost/tournaments/{$tournament->id}/waiver", ['agree' => true, 'signature' => 'Alpha Player']);

        tenancy()->initialize($beta);
        $this->assertSame(0, TournamentWaiver::count(), 'beta must not see alpha waivers');
        tenancy()->end();
    }
}
