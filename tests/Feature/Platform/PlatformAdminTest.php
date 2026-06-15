<?php

namespace Tests\Feature\Platform;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Actions\ReactivateClub;
use App\Domains\Tenancy\Actions\SuspendClub;
use App\Domains\Tenancy\Enums\ClubStatus;
use App\Domains\Tenancy\Events\ClubReactivated;
use App\Domains\Tenancy\Events\ClubSuspended;
use App\Domains\Tenancy\Models\Tenant;
use App\Http\Middleware\EnsureClubActive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PlatformAdminTest extends TestCase
{
    use RefreshDatabase;

    private function platformAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();

        return $admin;
    }

    private function makeClub(string $slug, ClubStatus $status = ClubStatus::Active): Tenant
    {
        $club = Tenant::create([
            'id' => $slug,
            'name' => ucfirst($slug).' Club',
            'slug' => $slug,
            'status' => $status,
        ]);
        $club->domains()->create(['domain' => $slug]);

        return $club;
    }

    public function test_new_clubs_default_to_active(): void
    {
        $club = Tenant::create(['name' => 'Fresh Club', 'slug' => 'fresh']);

        $this->assertSame(ClubStatus::Active, $club->fresh()->status);
        $this->assertTrue($club->fresh()->isActive());
    }

    public function test_a_platform_admin_can_list_clubs(): void
    {
        $this->makeClub('alpha');
        $this->makeClub('beta');

        $response = $this->withoutVite()
            ->actingAs($this->platformAdmin())
            ->get('http://localhost/admin/clubs');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('platform/clubs/index')
            ->has('clubs', 2)
        );
    }

    public function test_clubs_index_reports_per_club_counts(): void
    {
        $alpha = $this->makeClub('alpha');
        $member = User::factory()->create();
        $alpha->users()->attach($member->id);

        $response = $this->withoutVite()
            ->actingAs($this->platformAdmin())
            ->get('http://localhost/admin/clubs');

        $response->assertInertia(fn ($page) => $page
            ->where('clubs.0.slug', 'alpha')
            ->where('clubs.0.counts.members', 1)
            ->where('clubs.0.counts.courts', 0)
            ->where('clubs.0.counts.tournaments', 0)
        );
    }

    public function test_a_platform_admin_can_suspend_and_reactivate_a_club(): void
    {
        Event::fake([ClubSuspended::class, ClubReactivated::class]);

        $club = $this->makeClub('alpha');
        $admin = $this->platformAdmin();

        $this->actingAs($admin)
            ->post('http://localhost/admin/clubs/'.$club->id.'/suspend')
            ->assertRedirect();

        $this->assertSame(ClubStatus::Suspended, $club->fresh()->status);
        Event::assertDispatched(ClubSuspended::class, fn (ClubSuspended $e) => $e->club->is($club));

        $this->actingAs($admin)
            ->post('http://localhost/admin/clubs/'.$club->id.'/reactivate')
            ->assertRedirect();

        $this->assertSame(ClubStatus::Active, $club->fresh()->status);
        Event::assertDispatched(ClubReactivated::class, fn (ClubReactivated $e) => $e->club->is($club));
    }

    public function test_suspend_is_idempotent_and_does_not_re_emit(): void
    {
        Event::fake([ClubSuspended::class]);

        $club = $this->makeClub('alpha', ClubStatus::Suspended);

        app(SuspendClub::class)->handle($club);

        $this->assertSame(ClubStatus::Suspended, $club->fresh()->status);
        Event::assertNotDispatched(ClubSuspended::class);
    }

    public function test_reactivate_is_idempotent_and_does_not_re_emit(): void
    {
        Event::fake([ClubReactivated::class]);

        $club = $this->makeClub('alpha', ClubStatus::Active);

        app(ReactivateClub::class)->handle($club);

        $this->assertSame(ClubStatus::Active, $club->fresh()->status);
        Event::assertNotDispatched(ClubReactivated::class);
    }

    public function test_active_and_suspended_scopes_filter_clubs(): void
    {
        $this->makeClub('alpha', ClubStatus::Active);
        $this->makeClub('beta', ClubStatus::Suspended);

        $this->assertSame(['alpha'], Tenant::active()->pluck('slug')->all());
        $this->assertSame(['beta'], Tenant::suspended()->pluck('slug')->all());
    }

    public function test_is_platform_admin_flag_is_shared_to_the_frontend_for_an_admin(): void
    {
        $response = $this->withoutVite()
            ->actingAs($this->platformAdmin())
            ->get('http://localhost/dashboard');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('auth.user.is_platform_admin', true)
        );
    }

    public function test_is_platform_admin_flag_is_false_and_not_leaked_for_a_normal_user(): void
    {
        $user = User::factory()->create(); // NOT a platform admin

        $response = $this->withoutVite()
            ->actingAs($user)
            ->get('http://localhost/dashboard');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('auth.user.is_platform_admin', false)
        );
    }

    public function test_a_non_platform_admin_is_forbidden_from_the_admin_area(): void
    {
        $this->makeClub('alpha');
        $user = User::factory()->create(); // NOT a platform admin

        $this->withoutVite()
            ->actingAs($user)
            ->get('http://localhost/admin/clubs')
            ->assertForbidden();
    }

    public function test_an_anonymous_visitor_is_redirected_to_login(): void
    {
        $this->withoutVite()
            ->get('http://localhost/admin/clubs')
            ->assertRedirect();
    }

    public function test_ensure_club_active_middleware_blocks_a_suspended_tenant(): void
    {
        $club = $this->makeClub('alpha', ClubStatus::Suspended);

        tenancy()->initialize($club);

        try {
            $passed = false;
            (new EnsureClubActive)->handle(Request::create('http://alpha.localhost/'), function () use (&$passed): Response {
                $passed = true;

                return new Response('ok');
            });
            $this->fail('Expected a 403 for a suspended club.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertFalse($passed);
        } finally {
            tenancy()->end();
        }
    }

    public function test_ensure_club_active_middleware_passes_an_active_tenant(): void
    {
        $club = $this->makeClub('alpha', ClubStatus::Active);

        tenancy()->initialize($club);

        $response = (new EnsureClubActive)->handle(Request::create('http://alpha.localhost/'), fn () => new Response('ok'));

        $this->assertSame('ok', $response->getContent());

        tenancy()->end();
    }
}
