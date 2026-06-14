<?php

namespace Tests\Feature\Auth;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_login_still_redirects_to_the_central_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->post('http://localhost/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_login_on_a_club_subdomain_redirects_to_the_club_dashboard_not_central(): void
    {
        $club = Tenant::create(['id' => 'alpha', 'name' => 'Alpha Club', 'slug' => 'alpha']);
        $club->domains()->create(['domain' => 'alpha']);

        $member = User::factory()->create();
        $club->users()->attach($member->id);

        $response = $this->post('http://alpha.localhost/login', [
            'email' => $member->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        // The club dashboard is "/", NOT the central-only "/dashboard" (which 404s here).
        $response->assertRedirect('/');
        $this->assertStringNotContainsString('/dashboard', (string) $response->headers->get('Location'));
    }
}
