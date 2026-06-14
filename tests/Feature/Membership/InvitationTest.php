<?php

namespace Tests\Feature\Membership;

use App\Domains\Identity\Models\User;
use App\Domains\Membership\Actions\AcceptInvitation;
use App\Domains\Membership\Events\MemberInvited;
use App\Domains\Membership\Listeners\SendInvitationEmail;
use App\Domains\Membership\Mail\InvitationMail;
use App\Domains\Membership\Models\Invitation;
use App\Domains\Tenancy\Models\Tenant;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    private RolePermissionSeeder $roleSeeder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleSeeder = app(RolePermissionSeeder::class);
        $this->roleSeeder->run(); // global permissions
    }

    private function makeClub(string $slug): Tenant
    {
        $club = Tenant::create([
            'id' => $slug,
            'name' => ucfirst($slug).' Club',
            'slug' => $slug,
        ]);
        $club->domains()->create(['domain' => $slug]);
        $this->roleSeeder->seedForTenant($club);

        return $club;
    }

    private function makeMember(Tenant $club, string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $club->users()->attach($user->id);

        app(PermissionRegistrar::class)->setPermissionsTeamId($club->getTenantKey());
        $user->unsetRelation('roles');
        $user->assignRole($role);

        return $user;
    }

    private function roleOf(Tenant $club, User $user): ?string
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($club->getTenantKey());
        $user->unsetRelation('roles');

        return $user->getRoleNames()->first();
    }

    public function test_admin_with_member_manage_can_invite_and_dispatches_event(): void
    {
        Event::fake([MemberInvited::class]);

        $club = $this->makeClub('alpha');
        $admin = $this->makeMember($club, 'club-admin');

        $response = $this->actingAs($admin)->post('http://alpha.localhost/invitations', [
            'email' => 'newcomer@example.com',
            'role' => 'member',
        ]);

        $response->assertRedirect();

        tenancy()->initialize($club);
        $invitation = Invitation::where('email', 'newcomer@example.com')->firstOrFail();
        $this->assertSame('member', $invitation->role);
        $this->assertSame($admin->id, $invitation->invited_by);
        $this->assertNull($invitation->accepted_at);
        $this->assertTrue($invitation->isPending());
        tenancy()->end();

        Event::assertDispatched(
            MemberInvited::class,
            fn (MemberInvited $e) => $e->invitation->email === 'newcomer@example.com',
        );
    }

    public function test_send_invitation_email_listener_mails_the_accept_link(): void
    {
        Mail::fake();

        $club = $this->makeClub('alpha');

        tenancy()->initialize($club);
        $invitation = Invitation::create([
            'email' => 'invitee@example.com',
            'role' => 'member',
            'token' => Invitation::generateToken(),
            'expires_at' => Invitation::defaultExpiry(),
        ]);
        tenancy()->end();

        (new SendInvitationEmail)->handle(new MemberInvited($invitation));

        Mail::assertSent(
            InvitationMail::class,
            fn (InvitationMail $m) => $m->hasTo('invitee@example.com') && $m->club->is($club),
        );
    }

    public function test_accepting_a_valid_invitation_creates_membership_and_assigns_role(): void
    {
        $club = $this->makeClub('alpha');

        tenancy()->initialize($club);
        $invitation = Invitation::create([
            'email' => 'joiner@example.com',
            'role' => 'coach',
            'token' => Invitation::generateToken(),
            'expires_at' => Invitation::defaultExpiry(),
        ]);

        $user = app(AcceptInvitation::class)->handle($invitation, name: 'Jo Joiner', password: 'password1234');
        tenancy()->end();

        // A brand-new user was provisioned and joined to the club.
        $this->assertSame('joiner@example.com', $user->email);
        $this->assertSame('Jo Joiner', $user->name);
        $this->assertTrue($club->users()->whereKey($user->id)->exists());

        // The invited role was granted within the club's team context.
        $this->assertSame('coach', $this->roleOf($club, $user));

        // The invitation is marked accepted.
        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
        $this->assertFalse($invitation->isPending());
    }

    public function test_accepting_uses_an_existing_account_when_the_email_already_exists(): void
    {
        $club = $this->makeClub('alpha');
        $existing = User::factory()->create(['email' => 'existing@example.com', 'name' => 'Existing User']);

        tenancy()->initialize($club);
        $invitation = Invitation::create([
            'email' => 'existing@example.com',
            'role' => 'member',
            'token' => Invitation::generateToken(),
            'expires_at' => Invitation::defaultExpiry(),
        ]);

        $user = app(AcceptInvitation::class)->handle($invitation);
        tenancy()->end();

        $this->assertTrue($user->is($existing));
        $this->assertSame('Existing User', $user->name);
        $this->assertSame(1, User::where('email', 'existing@example.com')->count());
        $this->assertTrue($club->users()->whereKey($existing->id)->exists());
        $this->assertSame('member', $this->roleOf($club, $existing));
    }

    public function test_a_non_admin_cannot_invite(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club, 'member');

        $this->actingAs($member)
            ->post('http://alpha.localhost/invitations', [
                'email' => 'nope@example.com',
                'role' => 'member',
            ])
            ->assertForbidden();
    }

    public function test_expired_invitation_is_rejected(): void
    {
        $club = $this->makeClub('alpha');

        tenancy()->initialize($club);
        $invitation = Invitation::create([
            'email' => 'late@example.com',
            'role' => 'member',
            'token' => Invitation::generateToken(),
            'expires_at' => Carbon::now()->subDay(),
        ]);
        tenancy()->end();

        // The GET accept page returns 410 Gone for a stale token.
        $this->withoutVite()
            ->get('http://alpha.localhost/invitations/'.$invitation->token.'/accept')
            ->assertGone();
    }

    public function test_invalid_token_is_rejected(): void
    {
        $club = $this->makeClub('alpha');

        $this->withoutVite()
            ->get('http://alpha.localhost/invitations/not-a-real-token/accept')
            ->assertNotFound();
    }

    public function test_invitations_are_isolated_per_club(): void
    {
        $alpha = $this->makeClub('alpha');
        $beta = $this->makeClub('beta');

        tenancy()->initialize($alpha);
        Invitation::create([
            'email' => 'shared@example.com',
            'role' => 'member',
            'token' => Invitation::generateToken(),
            'expires_at' => Invitation::defaultExpiry(),
        ]);
        $this->assertSame(1, Invitation::count());
        tenancy()->end();

        // Beta sees none of Alpha's invitations.
        tenancy()->initialize($beta);
        $this->assertSame(0, Invitation::count());
        tenancy()->end();
    }

    public function test_accepting_via_http_logs_the_user_in_and_redirects_to_the_club(): void
    {
        $club = $this->makeClub('alpha');

        tenancy()->initialize($club);
        $invitation = Invitation::create([
            'email' => 'web@example.com',
            'role' => 'member',
            'token' => Invitation::generateToken(),
            'expires_at' => Invitation::defaultExpiry(),
        ]);
        tenancy()->end();

        $response = $this->withoutVite()->post('http://alpha.localhost/invitations/'.$invitation->token.'/accept', [
            'name' => 'Web Joiner',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('alpha.localhost', (string) $response->headers->get('Location'));

        $user = User::where('email', 'web@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($club->users()->whereKey($user->id)->exists());
        $this->assertSame('member', $this->roleOf($club, $user));
    }
}
