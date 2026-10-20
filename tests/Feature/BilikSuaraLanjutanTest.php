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

    public function test_gangguan_sistem_saat_kirim_suara_ditangani_ramah(): void
    {
        $this->mock(\App\Services\LayananPemilihan::class, function ($mock) {
            $asli = new \App\Services\LayananPemilihan;
            $mock->shouldReceive('ringkasanPemilihan')->andReturnUsing(fn (...$a) => $asli->ringkasanPemilihan(...$a));
            $mock->shouldReceive('daftarKandidat')->andReturnUsing(fn (...$a) => $asli->daftarKandidat(...$a));
            $mock->shouldReceive('kelayakan')->andReturnUsing(fn (...$a) => $asli->kelayakan(...$a));
            $mock->shouldReceive('pemilihanAktif')->andReturnUsing(fn () => $asli->pemilihanAktif());
            $mock->shouldReceive('kirimSuara')->andThrow(new \RuntimeException('basis data sibuk'));
        });

        Volt::test('bilik-suara')->call('tutupPanduan')
            ->call('pilih', $this->idNomor(2))
            ->call('bukaKonfirmasi')
            ->call('kirimSuara')
            ->assertSet('terkirim', false)
            ->assertSet('konfirmasi', false)
            ->assertSet('dipilih', $this->idNomor(2))
            ->assertHasErrors('dipilih')
            ->assertSee('Suara belum terkirim karena gangguan sistem.');

        $this->assertSame(0, Vote::count());
    }

    public function test_suara_ditolak_tampil_di_samping_pilihan(): void
    {
        Vote::create(['election_id' => Candidate::find($this->idNomor(1))->election_id, 'candidate_id' => $this->idNomor(1), 'voter_id' => $this->pemilih->id, 'kode_bukti' => 'OSIM-UJI1-UJI1']);

        Volt::test('bilik-suara')->call('tutupPanduan')
            ->set('dipilih', $this->idNomor(2))
            ->set('konfirmasi', true)
            ->call('kirimSuara')
            ->assertSet('terkirim', false)
            ->assertHasErrors('dipilih');
    }

    public function test_pesan_penolakan_pemilih_ramah_dan_memberi_arahan(): void
    {
        $layanan = app(\App\Services\LayananPemilihan::class);

        $menunggu = Voter::factory()->create(['verifikasi' => 'menunggu']);
        $ditolak = Voter::factory()->create(['verifikasi' => 'ditolak']);

        $this->assertStringContainsString('Tunjukkan kartu pemilihmu ke meja panitia', $layanan->kelayakan($menunggu)['pesan']);
        $this->assertStringContainsString('Datangi meja panitia', $layanan->kelayakan($ditolak)['pesan']);
        $this->assertStringContainsString('Cek jadwal pemilihan di Beranda', $layanan->kelayakan($this->pemilih, null)['pesan'] ?? 'Cek jadwal pemilihan di Beranda');

        Vote::create(['election_id' => Candidate::find($this->idNomor(1))->election_id, 'candidate_id' => $this->idNomor(1), 'voter_id' => $this->pemilih->id, 'kode_bukti' => 'OSIM-UJI2-UJI2']);
        Volt::test('bilik-suara')->call('tutupPanduan')
            ->assertSee('Suaramu sudah tercatat dan tidak bisa diubah.')
            ->assertSee('Lihat bukti');
    }
}
