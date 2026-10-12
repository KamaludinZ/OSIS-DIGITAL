<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_dashboard_mengarah_ke_beranda_untuk_pengguna_masuk(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/dashboard')->assertRedirect('/');
    }
}
