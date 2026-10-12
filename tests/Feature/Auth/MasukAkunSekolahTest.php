<?php

namespace Tests\Feature\Auth;

use App\Enums\Peran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class MasukAkunSekolahTest extends TestCase
{
    use RefreshDatabase;

    public function test_masuk_mencatat_waktu_dan_email_tidak_peka_huruf(): void
    {
        $user = User::factory()->create(['role' => Peran::Panitia, 'email' => 'panitia@osim.test']);

        Volt::test('auth.login')->set('email', '  Panitia@OSIM.test ')->set('password', 'password')->call('login')
            ->assertHasNoErrors()->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->terakhir_masuk_pada);
    }

    public function test_akun_nonaktif_ditolak_dan_sesi_berjalan_diputus(): void
    {
        $user = User::factory()->create(['role' => Peran::Pengurus, 'aktif' => false]);

        Volt::test('auth.login')->set('email', $user->email)->set('password', 'password')->call('login')
            ->assertHasErrors(['email']);
        $this->assertGuest();

        $aktif = User::factory()->create(['role' => Peran::Pengurus]);
        $this->actingAs($aktif)->get('/settings/profile')->assertOk();

        $aktif->update(['aktif' => false]);
        $this->actingAs($aktif->fresh())->get('/settings/profile')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->actingAs($aktif->fresh())->get('/admin/anggota')->assertRedirect(route('login'));
    }

    public function test_salah_kata_sandi_berulang_dibatasi(): void
    {
        $user = User::factory()->create(['role' => Peran::Admin]);

        foreach (range(1, 5) as $i) {
            Volt::test('auth.login')->set('email', $user->email)->set('password', 'salah')->call('login')->assertHasErrors(['email']);
        }

        Volt::test('auth.login')->set('email', $user->email)->set('password', 'password')->call('login')
            ->assertHasErrors(['email']);
        $this->assertGuest();
    }
}
