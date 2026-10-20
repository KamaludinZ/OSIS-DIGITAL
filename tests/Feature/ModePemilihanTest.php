<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Filament\Resources\Elections\Pages\ListElections;
use App\Models\OsisPeriod;
use App\Models\User;
use App\Services\LayananModePemilihan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModePemilihanTest extends TestCase
{
    use RefreshDatabase;

    private OsisPeriod $periode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->periode = OsisPeriod::factory()->tahun(2026)->aktif()->create(['mode_pemilihan' => true]);
    }

    public function test_halaman_periode_pemilihan_menampilkan_status_mode(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));

        $this->get('/admin/periode-pemilihan')->assertOk()
            ->assertSeeInOrder(['Mode pemilihan', 'Aktif', 'Menu publik menampilkan Bilik Suara, Kandidat, dan Hasil.'])
            ->assertSee('Periode 2026/2027');

        app(LayananModePemilihan::class)->ubah(false, User::factory()->create(['name' => 'Admin Sekolah']));

        $this->get('/admin/periode-pemilihan')->assertOk()
            ->assertSeeInOrder(['Mode pemilihan', 'Nonaktif', 'Struktur Organisasi dan Kotak Saran'])
            ->assertSee('Terakhir diubah')
            ->assertSee('oleh Admin Sekolah');
    }

    public function test_tombol_ubah_mode_berlabel_sesuai_mode_dan_menyalakan_mematikan(): void
    {
        $this->actingAs($panitia = User::factory()->create(['role' => Peran::Panitia, 'name' => 'Bu Panitia']));

        $halaman = Livewire::test(ListElections::class)
            ->assertActionHasLabel('ubahMode', 'Matikan mode pemilihan')
            ->assertActionHasColor('ubahMode', 'danger')
            ->callAction('ubahMode')
            ->assertNotified('Mode pemilihan dimatikan');

        $this->periode->refresh();
        $this->assertFalse($this->periode->mode_pemilihan);
        $this->assertTrue($this->periode->pengubahMode->is($panitia));
        $this->assertNotNull($this->periode->mode_diubah_pada);

        $halaman->assertActionHasLabel('ubahMode', 'Nyalakan mode pemilihan')
            ->assertActionHasColor('ubahMode', 'success')
            ->callAction('ubahMode')
            ->assertNotified('Mode pemilihan dinyalakan');

        $this->assertTrue($this->periode->fresh()->mode_pemilihan);
    }

    public function test_mode_mengikuti_periode_aktif(): void
    {
        $layanan = app(LayananModePemilihan::class);
        $this->assertTrue($layanan->aktif());

        // Periode baru diaktifkan: mulai dalam mode nonaktif (bawaan periode baru).
        $baru = OsisPeriod::factory()->tahun(2027)->create();
        app(\App\Services\LayananPeriode::class)->aktifkan($baru);
        $this->assertFalse($layanan->aktif());

        // Tanpa periode aktif: tetap mode pemilihan, sakelar tidak bisa diubah.
        $baru->update(['status' => 'arsip']);
        $this->assertTrue($layanan->aktif());
        $this->expectException(\App\Exceptions\PeriodeDitolak::class);
        $layanan->ubah(false, User::factory()->create());
    }

    public function test_tombol_nonaktif_tanpa_periode_aktif(): void
    {
        $this->periode->update(['status' => 'arsip']);
        $this->actingAs(User::factory()->create(['role' => Peran::Admin]));

        Livewire::test(ListElections::class)->assertActionDisabled('ubahMode');
        $this->get('/admin/periode-pemilihan')->assertOk()->assertSee('Belum ada periode aktif');
    }

    public function test_dialog_konfirmasi_menjelaskan_dampak_perubahan_mode(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Admin]));

        Livewire::test(ListElections::class)
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

        Livewire::test(ListElections::class)
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
            Livewire::test(ListElections::class)->assertActionVisible('ubahMode');
        }
    }
}
