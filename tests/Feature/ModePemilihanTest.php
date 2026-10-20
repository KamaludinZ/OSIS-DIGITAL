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

    public function test_tombol_ubah_mode_berlabel_sesuai_mode_dan_menyalakan_mematikan(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia, 'name' => 'Bu Panitia']));

        $halaman = \Livewire\Livewire::test(\App\Filament\Resources\Elections\Pages\ListElections::class)
            ->assertActionHasLabel('ubahMode', 'Matikan mode pemilihan')
            ->assertActionHasColor('ubahMode', 'danger')
            ->callAction('ubahMode')
            ->assertNotified('Mode pemilihan dimatikan');

        $this->assertFalse(\App\Support\DataTiruanModePemilihan::aktif());
        $this->assertSame('Bu Panitia', \App\Support\DataTiruanModePemilihan::status()['diubah_oleh']);

        $halaman->assertActionHasLabel('ubahMode', 'Nyalakan mode pemilihan')
            ->assertActionHasColor('ubahMode', 'success')
            ->callAction('ubahMode')
            ->assertNotified('Mode pemilihan dinyalakan');

        $this->assertTrue(\App\Support\DataTiruanModePemilihan::aktif());
    }
}
