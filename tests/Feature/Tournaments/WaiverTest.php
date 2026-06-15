<?php

namespace Tests\Feature\Tournaments;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tournaments\Events\WaiverSigned;
use App\Domains\Tournaments\Models\Tournament;
use App\Domains\Tournaments\Models\TournamentWaiver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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
