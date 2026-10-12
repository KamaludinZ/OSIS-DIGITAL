<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt as LivewireVolt;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create(['role' => \App\Enums\Peran::Panitia]);

        $response = LivewireVolt::test('auth.login')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login');

        $response
            ->assertHasNoErrors()
            ->assertRedirect('/admin');

        $this->assertAuthenticated();
    }

    public function test_login_hanya_untuk_staf(): void
    {
        $siswa = User::factory()->create(['role' => \App\Enums\Peran::Siswa]);

        LivewireVolt::test('auth.login')
            ->set('email', $siswa->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_tidak_ada_pendaftaran_maupun_reset_kata_sandi_mandiri(): void
    {
        $this->get('/register')->assertNotFound();
        $this->get('/forgot-password')->assertNotFound();

        $this->get('/login')
            ->assertOk()
            ->assertSee('Kembali ke Beranda')
            ->assertSee('Belum punya akun atau lupa kata sandi?')
            ->assertDontSee('Sign up')
            ->assertDontSee('Forgot your password');
    }
}
