<?php

namespace Tests\Feature\Tournaments;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tournaments\Events\WaiverTemplateUpdated;
use App\Domains\Tournaments\Models\ClubWaiverTemplate;
use App\Domains\Tournaments\Support\DefaultWaiver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WaiverTemplateTest extends TestCase
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

    public function test_an_organiser_can_update_the_waiver_template(): void
    {
        Event::fake([WaiverTemplateUpdated::class]);

        $club = $this->makeClub('alpha');
        $organiser = $this->makeMember($club, 'club-admin');

        $this->actingAs($organiser)
            ->put('http://alpha.localhost/tournaments/waiver-template', [
                'clauses' => ['First clause.', 'Second clause about {tournament}.'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('club_waiver_templates', ['tenant_id' => $club->id]);

        tenancy()->initialize($club);
        $this->assertSame(
            ['First clause.', 'Second clause about {tournament}.'],
            ClubWaiverTemplate::current()->clauses,
        );
        tenancy()->end();

        Event::assertDispatched(WaiverTemplateUpdated::class);
    }

    public function test_an_organiser_can_load_the_editor_with_the_right_props(): void
    {
        $club = $this->makeClub('alpha');
        $organiser = $this->makeMember($club, 'club-admin');

        // No custom template yet → defaults, isCustomised = false.
        $this->withoutVite()->actingAs($organiser)
            ->get('http://alpha.localhost/tournaments/waiver-template')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('tournaments/waiver-template')
                ->where('isCustomised', false)
                ->where('clauses', DefaultWaiver::clauses())
                ->where('defaults', DefaultWaiver::clauses()));

        // After customising → the custom clauses, isCustomised = true.
        tenancy()->initialize($club);
        ClubWaiverTemplate::create(['clauses' => ['Custom A.', 'Custom B for {tournament}.']]);
        tenancy()->end();

        $this->withoutVite()->actingAs($organiser)
            ->get('http://alpha.localhost/tournaments/waiver-template')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('isCustomised', true)
                ->where('clauses', ['Custom A.', 'Custom B for {tournament}.']));
    }

    public function test_a_plain_member_cannot_view_or_edit_the_template(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club);

        $this->actingAs($member)
            ->get('http://alpha.localhost/tournaments/waiver-template')
            ->assertForbidden();

        $this->actingAs($member)
            ->put('http://alpha.localhost/tournaments/waiver-template', ['clauses' => ['Nope.']])
            ->assertForbidden();
    }

    public function test_empty_or_blank_clauses_are_rejected(): void
    {
        $club = $this->makeClub('alpha');
        $organiser = $this->makeMember($club, 'club-admin');

        $this->actingAs($organiser)
            ->put('http://alpha.localhost/tournaments/waiver-template', ['clauses' => ['', '   ']])
            ->assertSessionHasErrors(['clauses']);

        tenancy()->initialize($club);
        $this->assertNull(ClubWaiverTemplate::current());
        tenancy()->end();
    }

    public function test_blank_rows_are_trimmed_away_keeping_only_real_clauses(): void
    {
        $club = $this->makeClub('alpha');
        $organiser = $this->makeMember($club, 'club-admin');

        $this->actingAs($organiser)
            ->put('http://alpha.localhost/tournaments/waiver-template', [
                'clauses' => ['  Keep me.  ', '', '   '],
            ])
            ->assertRedirect();

        tenancy()->initialize($club);
        $this->assertSame(['Keep me.'], ClubWaiverTemplate::current()->clauses);
        tenancy()->end();
    }

    public function test_a_club_without_a_template_falls_back_to_the_defaults(): void
    {
        $club = $this->makeClub('alpha');

        tenancy()->initialize($club);
        $this->assertNull(ClubWaiverTemplate::current());
        $this->assertSame(DefaultWaiver::clauses(), ClubWaiverTemplate::clausesForClub());
        tenancy()->end();
    }

    public function test_updating_keeps_one_row_per_club(): void
    {
        $club = $this->makeClub('alpha');
        $organiser = $this->makeMember($club, 'club-admin');

        $this->actingAs($organiser)->put('http://alpha.localhost/tournaments/waiver-template', ['clauses' => ['One.']]);
        $this->actingAs($organiser)->put('http://alpha.localhost/tournaments/waiver-template', ['clauses' => ['Two.', 'Three.']]);

        tenancy()->initialize($club);
        $this->assertSame(1, ClubWaiverTemplate::count());
        $this->assertSame(['Two.', 'Three.'], ClubWaiverTemplate::current()->clauses);
        tenancy()->end();
    }

    public function test_templates_are_isolated_between_clubs(): void
    {
        $alpha = $this->makeClub('alpha');
        $beta = $this->makeClub('beta');
        $organiser = $this->makeMember($alpha, 'club-admin');

        $this->actingAs($organiser)->put('http://alpha.localhost/tournaments/waiver-template', ['clauses' => ['Alpha only.']]);

        tenancy()->initialize($beta);
        $this->assertSame(0, ClubWaiverTemplate::count(), 'beta must not see alpha templates');
        tenancy()->end();
    }
}
