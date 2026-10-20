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
}
