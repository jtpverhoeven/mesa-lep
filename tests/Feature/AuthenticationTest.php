<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_from_the_landing_page_to_login(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
        $this->get('/login')->assertOk()->assertSee('MESA | Inloggen');
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_development_administrator_can_log_in(): void
    {
        $this->seed();

        $this->post('/login', [
            'login' => 'admin',
            'password' => 'check',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs(User::where('alias', 'admin')->first());
        $this->assertTrue(auth()->user()->hasRole('administrator'));
    }
}
