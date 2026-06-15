<?php

namespace Tests\Feature\Auth;

use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Where members land after logging in on the central domain. Platform admins have no club,
 * so they go to the clubs list rather than the empty starter dashboard (which read as a
 * "blank screen").
 */
class PostLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_lands_on_the_clubs_list(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();

        $this->post('http://localhost/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('platform.clubs.index'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_a_central_user_without_a_club_lands_on_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->post('http://localhost/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
    }
}
