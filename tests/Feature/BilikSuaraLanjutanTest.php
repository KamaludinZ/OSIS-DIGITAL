<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Vote;
use App\Models\Voter;
use App\Services\SesiPemilih;
use Database\Seeders\PemilihanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

/** Pelengkapan spesifikasi bilik suara: pilih & konfirmasi, bukti, panduan. */
class BilikSuaraLanjutanTest extends TestCase
{
    use RefreshDatabase;

    private Voter $pemilih;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PemilihanSeeder::class);
        $this->pemilih = Voter::factory()->berkode('246810')->create(['nama' => 'Budi Santoso', 'kelas' => 'VII-C']);
        session([SesiPemilih::KUNCI_PEMILIH => $this->pemilih->id, 'pemilih_masuk_pada' => now()->timestamp]);
    }

    private function idNomor(int $nomor): int
    {
        return Candidate::where('nomor_urut', $nomor)->value('id');
    }

    public function test_modal_konfirmasi_menampilkan_pilihan_dan_peringatan(): void
    {
        $calon = Candidate::find($this->idNomor(2));

        Volt::test('bilik-suara')->call('tutupPanduan')
            ->call('pilih', $calon->id)
            ->call('bukaKonfirmasi')
            ->assertSet('konfirmasi', true)
            ->assertSee('Konfirmasi pilihan')
            ->assertSeeHtml('Apakah Anda yakin memilih <strong class="text-tinta">nomor 2</strong>?')
            ->assertSee($calon->nama_ketua)
            ->assertSee('Suara yang sudah dikirim tidak bisa diubah atau dibatalkan.')
            ->assertSee('Batal, pilih ulang')
            ->assertSee('Kirim Suara');
    }

    public function test_batal_di_modal_tidak_mengirim_suara(): void
    {
        Volt::test('bilik-suara')->call('tutupPanduan')
            ->call('pilih', $this->idNomor(1))
            ->call('bukaKonfirmasi')
            ->set('konfirmasi', false) // tombol "Batal, pilih ulang" menutup modal
            ->call('kirimSuara')
            ->assertSet('terkirim', false)
            ->assertSet('dipilih', $this->idNomor(1))
            ->call('pilih', $this->idNomor(3))
            ->assertSet('dipilih', $this->idNomor(3));

        $this->assertSame(0, Vote::count());
    }
}
