<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModePemilihanTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_periode_pemilihan_menampilkan_status_mode(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));

        $this->get('/admin/periode-pemilihan')->assertOk()
            ->assertSeeInOrder(['Mode pemilihan', 'Aktif', 'Menu publik menampilkan Bilik Suara, Kandidat, dan Hasil.'])
            ->assertSee('Terakhir diubah');

        \App\Support\DataTiruanModePemilihan::setel(false, 'Admin Sekolah');

        $this->get('/admin/periode-pemilihan')->assertOk()
            ->assertSeeInOrder(['Mode pemilihan', 'Nonaktif', 'Struktur Organisasi dan Kotak Saran'])
            ->assertSee('oleh Admin Sekolah');
    }
}
