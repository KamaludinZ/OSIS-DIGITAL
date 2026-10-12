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
}
