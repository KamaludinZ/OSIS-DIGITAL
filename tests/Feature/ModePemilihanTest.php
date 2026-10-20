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

    public function test_dialog_konfirmasi_menjelaskan_dampak_perubahan_mode(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Admin]));

        \Livewire\Livewire::test(\App\Filament\Resources\Elections\Pages\ListElections::class)
            ->mountAction('ubahMode')
            ->assertMountedActionModalSee(['Matikan mode pemilihan?', 'Struktur Organisasi dan Kotak Saran', 'dikunci', 'Ya, matikan'])
            ->callMountedAction()
            ->mountAction('ubahMode')
            ->assertMountedActionModalSee(['Nyalakan mode pemilihan?', 'Bilik Suara, Kandidat, dan Hasil', 'Ya, nyalakan']);
    }

    public function test_pratinjau_menu_publik_kedua_mode(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));

        $this->get('/admin/periode-pemilihan')->assertOk()
            ->assertSeeInOrder(['Saat mode aktif', 'Sedang tampil', 'Beranda', 'Bilik Suara', 'Kandidat', 'Hasil',
                'Saat mode nonaktif', 'Beranda', 'Struktur Organisasi', 'Kotak Saran']);

        \Livewire\Livewire::test(\App\Filament\Resources\Elections\Pages\ListElections::class)
            ->mountAction('ubahMode')
            ->assertMountedActionModalSee(['Saat mode nonaktif', 'Struktur Organisasi', 'Kotak Saran']);
    }

    public function test_kendali_mode_tersembunyi_bagi_yang_tidak_berhak(): void
    {
        // Pengurus hanya melihat status mode di halaman Periode OSIM, tanpa tombol.
        $this->actingAs(User::factory()->pengurusMenjabat()->create());
        $this->get('/admin/periode-osim')->assertOk()
            ->assertSeeInOrder(['Mode pemilihan', 'Aktif'])
            ->assertSee('Hanya panitia atau admin yang bisa mengubah mode.')
            ->assertDontSee('Matikan mode pemilihan');
        $this->get('/admin/periode-pemilihan')->assertForbidden();
        $this->assertFalse(auth()->user()->can('ubah-mode-pemilihan'));

        foreach ([Peran::Panitia, Peran::Admin] as $peran) {
            $this->actingAs(User::factory()->create(['role' => $peran]));
            $this->get('/admin/periode-pemilihan')->assertOk()->assertDontSee('Hanya panitia atau admin yang bisa mengubah mode.');
            \Livewire\Livewire::test(\App\Filament\Resources\Elections\Pages\ListElections::class)->assertActionVisible('ubahMode');
        }
    }
}
