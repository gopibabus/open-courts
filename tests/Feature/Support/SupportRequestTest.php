<?php

namespace Tests\Feature\Support;

use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Listeners\SendSupportRequestNotification;
use App\Domains\Notifications\Mail\SupportRequestMail;
use App\Domains\Support\Events\SupportRequestSubmitted;
use App\Domains\Support\Models\SupportRequest;
use App\Domains\Tenancy\Models\Tenant;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SupportRequestTest extends TestCase
{
    use RefreshDatabase;

    /** Create a club with its domain + roles seeded, returning the tenant. */
    private function makeClub(string $slug): Tenant
    {
        $club = Tenant::create([
            'id' => $slug,
            'name' => ucfirst($slug).' Club',
            'slug' => $slug,
        ]);
        $club->domains()->create(['domain' => $slug]);

        $roleSeeder = app(RolePermissionSeeder::class);
        $roleSeeder->run();
        $roleSeeder->seedForTenant($club);

        return $club;
    }

    /** Create a member of $club (no club-scoped permission needed for Help). */
    private function makeMember(Tenant $club): User
    {
        $user = User::factory()->create();
        $club->users()->attach($user->id);

        return $user;
    }

    public function test_help_page_renders(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club);

        $this->actingAs($member)
            ->withoutVite()
            ->get('http://alpha.localhost/help')
            ->assertOk();
    }

    public function test_member_can_submit_a_support_request(): void
    {
        Event::fake([SupportRequestSubmitted::class]);

        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club);

        $response = $this->actingAs($member)->post('http://alpha.localhost/help', [
            'category' => 'booking',
            'subject' => 'Court 2 lights are out',
            'message' => 'The floodlights on court 2 do not switch on after 8pm.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('support_requests', [
            'tenant_id' => $club->id,
            'user_id' => $member->id,
            'category' => 'booking',
            'subject' => 'Court 2 lights are out',
            'status' => 'open',
        ]);

        Event::assertDispatched(
            SupportRequestSubmitted::class,
            fn (SupportRequestSubmitted $e) => $e->supportRequest->subject === 'Court 2 lights are out'
                && $e->supportRequest->user_id === $member->id,
        );
    }

    public function test_submission_is_validated(): void
    {
        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club);

        $this->actingAs($member)
            ->post('http://alpha.localhost/help', [
                'category' => 'not-a-category',
                'subject' => '',
                'message' => 'too short',
            ])
            ->assertSessionHasErrors(['category', 'subject', 'message']);
    }

    public function test_support_notification_email_is_sent_to_the_inbox(): void
    {
        Mail::fake();

        $club = $this->makeClub('alpha');
        $member = $this->makeMember($club);

        tenancy()->initialize($club);
        $request = SupportRequest::create([
            'user_id' => $member->id,
            'category' => 'other',
            'subject' => 'Question about teams',
            'message' => 'How do tournament squads work?',
        ]);
        tenancy()->end();

        (new SendSupportRequestNotification)->handle(new SupportRequestSubmitted($request));

        Mail::assertSent(
            SupportRequestMail::class,
            fn (SupportRequestMail $m) => $m->hasTo(config('branding.support_email')),
        );
    }

    public function test_support_requests_are_isolated_between_clubs(): void
    {
        $alpha = $this->makeClub('alpha');
        $beta = $this->makeClub('beta');
        $member = $this->makeMember($alpha);

        tenancy()->initialize($alpha);
        SupportRequest::create([
            'user_id' => $member->id,
            'category' => 'other',
            'subject' => 'Alpha-only request',
            'message' => 'Visible to alpha only.',
        ]);
        tenancy()->end();

        tenancy()->initialize($beta);
        $this->assertSame(0, SupportRequest::count(), 'beta must not see alpha support requests');
        tenancy()->end();
    }
}
