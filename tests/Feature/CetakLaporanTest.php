<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Filament\Pages\CetakLaporan;
use App\Models\User;
use App\Models\Voter;
use Database\Seeders\PemilihanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CetakLaporanTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_cetak_dan_laporan(): void
    {
        $this->seed(PemilihanSeeder::class);
        Voter::factory()->count(2)->berkode()->create(['kelas' => 'VII-A']);
        Voter::factory()->create(['kelas' => 'VII-B', 'kode_pilih' => null]);

        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        $this->get('/admin/cetak-laporan')->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $this->get('/admin/cetak-laporan')->assertOk()
            ->assertSee('Kartu pemilih')
            ->assertSee('kartu siap dicetak')
            ->assertSee('1 pemilih terverifikasi belum punya kode')
            ->assertSee('Kelas VII-A (2)')
            ->assertSee('Berita acara')
            ->assertSee(route('hasil.laporan.csv'));

        Livewire::test(CetakLaporan::class)
            ->set('kelompok', 'VII-A')
            ->assertSee(route('kartu-pemilih', ['kelompok' => 'VII-A']));
    }

    public function test_pratinjau_kartu_siap_gunting_dengan_data_contoh(): void
    {
        $asli = Voter::factory()->berkode('987654')->create(['nama' => 'Siswa Asli']);
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));

        $this->get('/admin/cetak-laporan')->assertSee(route('kartu-pemilih', ['contoh' => 1]));

        $this->get(route('kartu-pemilih', ['contoh' => 1]))->assertOk()
            ->assertSee('Pratinjau tata letak dengan data contoh')
            ->assertSee('Ahmad Contoh')
            ->assertSee('123456')
            ->assertDontSee('Siswa Asli')
            ->assertDontSee('987654');

        // Tetap hanya untuk pengelola pemilih.
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        $this->get(route('kartu-pemilih', ['contoh' => 1]))->assertForbidden();
    }

    public function test_gaya_cetak_kartu_dan_berita_acara(): void
    {
        $this->seed(PemilihanSeeder::class);
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));

        $this->get(route('kartu-pemilih', ['contoh' => 1]))
            ->assertSee('@page { size: A4 portrait; margin: 10mm; }', false)
            ->assertSee('print-color-adjust: exact', false);

        $this->get(route('hasil.laporan'))->assertOk()
            ->assertSee('@page { size: A4 portrait;', false)
            ->assertSee('tr, .ttd { break-inside: avoid; }', false)
            ->assertSee('class="ttd', false);
    }
}
