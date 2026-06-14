<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The marketing home page is a public, guest-accessible Inertia page rendered at
 * route('home') on the central domain. These guard its server contract: it renders
 * for anonymous visitors (no auth redirect) and resolves to the 'welcome' component.
 */
class HomePageTest extends TestCase
{
    public function test_the_home_page_renders_for_guests(): void
    {
        $response = $this->withoutVite()->get('http://localhost/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('welcome'));
    }

    public function test_the_home_page_does_not_require_authentication(): void
    {
        // No auth middleware: a guest gets 200, never a redirect to login.
        $this->withoutVite()->get('http://localhost/')->assertOk();
    }
}
