<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenjagaRuteTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_login_dari_halaman_berakun(): void
    {
        foreach (['/admin', '/settings/profile', '/kelola/sosmed', '/dashboard'] as $url) {
            $this->get($url)->assertRedirect(route('login')); // satu halaman login OSIM, termasuk untuk /admin
        }
        // Halaman publik tetap terbuka tanpa akun.
        foreach (['/', '/bilik-suara', '/kandidat', '/hasil'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_halaman_awal_dan_alamat_login_sesuai_peran(): void
    {
        $calon = Candidate::factory()->create();
        $kandidat = User::factory()->create(['role' => Peran::Kandidat, 'candidate_id' => $calon->id]);
        $tanyaJawab = route('kandidat.profil', ['id' => $calon->id, 'tab' => 'tanya-jawab']);

        $this->actingAs($kandidat)->get('/login')->assertRedirect($tanyaJawab);
        $this->actingAs($kandidat)->get('/dashboard')->assertRedirect($tanyaJawab);
        $this->actingAs($kandidat)->get('/admin')->assertRedirect($tanyaJawab);
        $this->actingAs($kandidat)->get('/kelola/sosmed')->assertForbidden();

        $panitia = User::factory()->create(['role' => Peran::Panitia]);
        $this->actingAs($panitia)->get('/login')->assertRedirect('/admin');
        $this->actingAs($panitia)->get('/dashboard')->assertRedirect('/admin');
        $this->actingAs($panitia)->get('/kelola/sosmed')->assertForbidden();

        $pengurus = User::factory()->create(['role' => Peran::Pengurus]);
        $this->actingAs($pengurus)->get('/kelola/sosmed')->assertOk();
    }

    public function test_keluar_akun_dan_status_sesi(): void
    {
        $panitia = User::factory()->create(['role' => Peran::Panitia, 'name' => 'Bu Sari']);

        $this->actingAs($panitia)->get('/')->assertSee('Masuk sebagai')->assertSee('Bu Sari')->assertSee('Panitia pemilihan');
        $this->actingAs($panitia)->followingRedirects()->get('/admin')->assertOk()->assertSee('Pengaturan akun')->assertSee('Lihat situs OSIM');

        $this->post('/logout')->assertRedirect('/')->assertSessionHas('status');
        $this->assertGuest();
        $this->followingRedirects()->get('/')->assertSee('href="'.route('login').'"', false);
    }

    public function test_reset_kata_sandi_memutus_sesi_lain(): void
    {
        $pengurus = User::factory()->create(['role' => Peran::Pengurus]);

        $this->actingAs($pengurus)->get('/settings/profile')->assertOk(); // sesi menyimpan sidik kata sandi
        $pengurus->forceFill(['password' => 'kata-sandi-baru-1'])->save();   // admin mereset

        $this->get('/settings/profile')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_keluar_dari_ruang_osim(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Admin]));

        $this->post(route('filament.admin.auth.logout'))->assertRedirect();
        $this->assertGuest();
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/logout')->assertStatus(405); // keluar hanya lewat POST (dilindungi CSRF)
    }
}
