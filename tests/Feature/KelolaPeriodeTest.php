<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KelolaPeriodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_hanya_pengurus_dan_admin_yang_membuka_kelola_periode(): void
    {
        $this->get('/admin/periode-osim')->assertRedirect('/login');

        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $this->get('/admin/periode-osim')->assertForbidden();

        foreach ([Peran::Pengurus, Peran::Admin] as $peran) {
            $this->actingAs(User::factory()->create(['role' => $peran]));
            $this->get('/admin/periode-osim')->assertOk();
        }
    }

    public function test_halaman_utama_menampilkan_periode_aktif_dan_arsip(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));

        $this->get('/admin/periode-osim')
            ->assertOk()
            ->assertSee('Periode OSIM')
            ->assertSeeInOrder(['Periode aktif', '2026/2027'])
            ->assertSee('2025/2026')
            ->assertSee('Arsip');
    }
}
