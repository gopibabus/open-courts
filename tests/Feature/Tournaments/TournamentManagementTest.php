<?php

namespace Tests\Feature\Tournaments;

use App\Domains\Identity\Models\User;
use App\Domains\Membership\Actions\ProvisionClubRoles;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tournaments\Actions\AddCategory;
use App\Domains\Tournaments\Actions\CreateTournament;
use App\Domains\Tournaments\Actions\OpenRegistration;
use App\Domains\Tournaments\Actions\RegisterEntrant;
use App\Domains\Tournaments\Data\AddCategoryData;
use App\Domains\Tournaments\Data\CreateTournamentData;
use App\Domains\Tournaments\Data\OpenRegistrationData;
use App\Domains\Tournaments\Data\RegisterEntrantData;
use App\Domains\Tournaments\Enums\CategoryType;
use App\Domains\Tournaments\Enums\RegistrationStatus;
use App\Domains\Tournaments\Enums\TournamentFormat;
use App\Domains\Tournaments\Events\EntrantRegistered;
use App\Domains\Tournaments\Events\RegistrationOpened;
use App\Domains\Tournaments\Events\TournamentCreated;
use App\Domains\Tournaments\Exceptions\RegistrationException;
use App\Domains\Tournaments\Models\Tournament;
use App\Domains\Tournaments\Models\TournamentCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TournamentManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Provision a club (tenant) with its roles, and return it.
     */
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

    /**
     * Create a user, make them a member of the club, and assign a club-scoped role.
     */
    private function makeMember(Tenant $club, string $role): User
    {
        $user = User::factory()->create();
        $club->users()->attach($user->id);

        app(PermissionRegistrar::class)->setPermissionsTeamId($club->getTenantKey());
        $user->assignRole($role);

        return $user;
    }

    public function test_club_admin_can_create_a_tenant_scoped_tournament(): void
    {
        Event::fake([TournamentCreated::class]);

        $club = $this->makeClub('alpha');

        tenancy()->initialize($club);
        $tournament = app(CreateTournament::class)->handle(new CreateTournamentData(
            name: 'Spring Open',
            format: TournamentFormat::SingleElimination,
            startsOn: '2026-07-01',
            endsOn: '2026-07-05',
        ));
        tenancy()->end();

        $this->assertSame('alpha', $tournament->tenant_id);
        $this->assertSame('draft', $tournament->status);
        $this->assertSame(TournamentFormat::SingleElimination, $tournament->format);

        Event::assertDispatched(
            TournamentCreated::class,
            fn (TournamentCreated $e) => $e->tournament->is($tournament),
        );
    }

    public function test_a_category_can_be_added_to_a_tournament(): void
    {
        $club = $this->makeClub('alpha');

        tenancy()->initialize($club);
        $tournament = app(CreateTournament::class)->handle(new CreateTournamentData(
            name: 'Spring Open',
            format: TournamentFormat::RoundRobin,
            startsOn: null,
            endsOn: null,
        ));
        $category = app(AddCategory::class)->handle($tournament, new AddCategoryData(
            name: "Men's Singles",
            type: CategoryType::Singles,
            maxEntrants: 8,
        ));
        tenancy()->end();

        $this->assertSame('alpha', $category->tenant_id);
        $this->assertSame(CategoryType::Singles, $category->type);
        $this->assertSame(8, $category->max_entrants);
        $this->assertTrue($tournament->categories()->whereKey($category->id)->exists());
    }

    public function test_opening_registration_sets_window_and_emits_event(): void
    {
        Event::fake([RegistrationOpened::class]);

        $club = $this->makeClub('alpha');

        tenancy()->initialize($club);
        $tournament = app(CreateTournament::class)->handle(new CreateTournamentData(
            name: 'Spring Open',
            format: TournamentFormat::SingleElimination,
            startsOn: null,
            endsOn: null,
        ));
        app(OpenRegistration::class)->handle($tournament, new OpenRegistrationData(
            opensOn: now()->subDay()->toDateString(),
            closesOn: now()->addWeek()->toDateString(),
        ));
        tenancy()->end();

        $tournament->refresh();
        $this->assertSame('open', $tournament->status);
        $this->assertNotNull($tournament->registration_opens_on);

        Event::assertDispatched(RegistrationOpened::class);
    }

    public function test_a_member_can_register_an_entrant(): void
    {
        Event::fake([EntrantRegistered::class]);

        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $category = $this->openCategory(maxEntrants: 8);

        $registration = app(RegisterEntrant::class)->handle($category, new RegisterEntrantData(
            userId: $member->id,
        ));
        tenancy()->end();

        $this->assertSame('alpha', $registration->tenant_id);
        $this->assertSame($member->id, $registration->user_id);
        $this->assertSame(RegistrationStatus::Pending, $registration->status);

        Event::assertDispatched(EntrantRegistered::class);
    }

    public function test_registration_is_rejected_when_at_capacity(): void
    {

        $club = $this->makeClub('alpha');
        $a = $this->makeMember($club, 'member');
        $b = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $category = $this->openCategory(maxEntrants: 1);

        app(RegisterEntrant::class)->handle($category, new RegisterEntrantData(userId: $a->id));

        $this->expectException(RegistrationException::class);
        app(RegisterEntrant::class)->handle($category, new RegisterEntrantData(userId: $b->id));
        tenancy()->end();
    }

    public function test_registration_is_rejected_for_a_duplicate_entrant(): void
    {

        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        $category = $this->openCategory(maxEntrants: 8);

        app(RegisterEntrant::class)->handle($category, new RegisterEntrantData(userId: $member->id));

        $this->expectException(RegistrationException::class);
        app(RegisterEntrant::class)->handle($category, new RegisterEntrantData(userId: $member->id));
        tenancy()->end();
    }

    public function test_registration_is_rejected_when_the_window_is_closed(): void
    {

        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member');

        tenancy()->initialize($club);
        // Tournament left in 'draft' — registration never opened.
        $tournament = app(CreateTournament::class)->handle(new CreateTournamentData(
            name: 'Spring Open',
            format: TournamentFormat::SingleElimination,
            startsOn: null,
            endsOn: null,
        ));
        $category = app(AddCategory::class)->handle($tournament, new AddCategoryData(
            name: "Men's Singles",
            type: CategoryType::Singles,
            maxEntrants: 8,
        ));

        $this->expectException(RegistrationException::class);
        app(RegisterEntrant::class)->handle($category, new RegisterEntrantData(userId: $member->id));
        tenancy()->end();
    }

    public function test_member_without_permission_cannot_create_a_tournament(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member'); // member lacks tournament.manage

        $response = $this->withoutVite()
            ->actingAs($member)
            ->post('http://alpha.localhost/tournaments', [
                'name' => 'Sneaky Cup',
                'format' => TournamentFormat::SingleElimination->value,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('tournaments', ['name' => 'Sneaky Cup']);
    }

    public function test_club_admin_can_create_a_tournament_over_http(): void
    {
        $club = $this->makeClub('alpha');
        $admin = $this->makeMember($club, 'club-admin');

        $response = $this->withoutVite()
            ->actingAs($admin)
            ->post('http://alpha.localhost/tournaments', [
                'name' => 'Club Championships',
                'format' => TournamentFormat::RoundRobin->value,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tournaments', [
            'name' => 'Club Championships',
            'tenant_id' => 'alpha',
            'status' => 'draft',
        ]);
    }

    public function test_tournaments_are_isolated_between_clubs(): void
    {
        $alpha = $this->makeClub('alpha');
        $beta = $this->makeClub('beta');

        tenancy()->initialize($alpha);
        app(CreateTournament::class)->handle(new CreateTournamentData(
            name: 'Alpha Cup',
            format: TournamentFormat::SingleElimination,
            startsOn: null,
            endsOn: null,
        ));
        tenancy()->end();

        tenancy()->initialize($beta);
        app(CreateTournament::class)->handle(new CreateTournamentData(
            name: 'Beta Cup',
            format: TournamentFormat::SingleElimination,
            startsOn: null,
            endsOn: null,
        ));
        $this->assertSame(1, Tournament::count());
        $this->assertSame('Beta Cup', Tournament::first()->name);
        tenancy()->end();

        tenancy()->initialize($alpha);
        $this->assertSame(1, Tournament::count());
        $this->assertSame('Alpha Cup', Tournament::first()->name);
        tenancy()->end();
    }

    /**
     * Create a tournament with one open category. Must be called inside an active tenant.
     */
    private function openCategory(?int $maxEntrants): TournamentCategory
    {
        $tournament = app(CreateTournament::class)->handle(new CreateTournamentData(
            name: 'Spring Open',
            format: TournamentFormat::SingleElimination,
            startsOn: null,
            endsOn: null,
        ));

        app(OpenRegistration::class)->handle($tournament, new OpenRegistrationData(
            opensOn: now()->subDay()->toDateString(),
            closesOn: now()->addWeek()->toDateString(),
        ));

        return app(AddCategory::class)->handle($tournament->refresh(), new AddCategoryData(
            name: "Men's Singles",
            type: CategoryType::Singles,
            maxEntrants: $maxEntrants,
        ));
    }
}
