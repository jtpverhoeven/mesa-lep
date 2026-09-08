<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_landing_and_login_pages(): void
    {
        $this->get('/')->assertOk()->assertSee('A quieter place to get things done.');
        $this->get('/login')->assertOk()->assertSee('Welcome back to Mesa.');
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_development_administrator_can_log_in(): void
    {
        $this->seed();

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs(User::where('email', 'admin@example.com')->first());
        $this->assertTrue(auth()->user()->hasRole('administrator'));
    }
}