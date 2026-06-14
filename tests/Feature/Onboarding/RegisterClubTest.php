<?php

namespace Tests\Feature\Onboarding;

use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Listeners\SendClubWelcomeEmail;
use App\Domains\Notifications\Mail\ClubWelcomeMail;
use App\Domains\Tenancy\Events\ClubRegistered;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RegisterClubTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'club_name' => 'Smash Tennis Club',
            'slug' => 'smashclub',
            'owner_name' => 'Sasha Owner',
            'owner_email' => 'owner@smashclub.test',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
        ], $overrides);
    }

    public function test_registration_screen_renders(): void
    {
        $this->withoutVite()->get('http://localhost/register-club')->assertOk();
    }

    public function test_registering_a_club_provisions_tenant_owner_and_roles(): void
    {
        Event::fake([ClubRegistered::class]);

        $response = $this->post('http://localhost/register-club', $this->payload());

        $owner = User::where('email', 'owner@smashclub.test')->firstOrFail();
        $club = Tenant::where('slug', 'smashclub')->firstOrFail();

        $this->assertSame('Smash Tennis Club', $club->name);
        $this->assertDatabaseHas('domains', ['domain' => 'smashclub', 'tenant_id' => $club->id]);
        $this->assertTrue($club->users()->whereKey($owner->id)->exists(), 'owner is a member of the club');

        // The owner is a club-admin scoped to this club.
        app(PermissionRegistrar::class)->setPermissionsTeamId($club->getTenantKey());
        $owner->unsetRelation('roles');
        $this->assertTrue($owner->hasRole('club-admin'));

        // Owner is signed in and bounced to their club subdomain.
        $this->assertAuthenticatedAs($owner);
        $response->assertRedirect();
        $this->assertStringContainsString('smashclub.localhost', (string) $response->headers->get('Location'));

        Event::assertDispatched(
            ClubRegistered::class,
            fn (ClubRegistered $e) => $e->club->is($club) && $e->owner->is($owner),
        );
    }

    public function test_welcome_email_is_sent_for_a_registered_club(): void
    {
        Mail::fake();

        $club = Tenant::create(['name' => 'Net Club', 'slug' => 'netclub']);
        $owner = User::factory()->create(['email' => 'owner@netclub.test']);

        (new SendClubWelcomeEmail)->handle(new ClubRegistered($club, $owner));

        Mail::assertSent(ClubWelcomeMail::class, fn (ClubWelcomeMail $m) => $m->hasTo('owner@netclub.test'));
    }

    public function test_slug_must_be_unique(): void
    {
        Tenant::create(['name' => 'Taken Club', 'slug' => 'taken']);

        $this->post('http://localhost/register-club', $this->payload(['slug' => 'taken']))
            ->assertSessionHasErrors('slug');
    }

    public function test_slug_must_be_dns_safe(): void
    {
        $this->post('http://localhost/register-club', $this->payload(['slug' => 'Bad Slug!']))
            ->assertSessionHasErrors('slug');
    }

    public function test_reserved_slug_is_rejected(): void
    {
        $this->post('http://localhost/register-club', $this->payload(['slug' => 'admin']))
            ->assertSessionHasErrors('slug');
    }
}
