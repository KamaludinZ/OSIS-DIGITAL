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

    public function test_halaman_panduan_cara_memilih(): void
    {
        $this->get('/panduan-memilih')->assertOk()
            ->assertSeeInOrder(['Empat langkah memilih', 'Kenali kandidat', 'Pilih satu nomor', 'Konfirmasi', 'Simpan bukti'])
            ->assertSee('Kartu pemilihku hilang atau kodenya salah.')
            ->assertSee('href="'.route('bilik-suara').'"', false);

        // Bilik suara menautkan panduan lengkap.
        Volt::test('bilik-suara')->assertSee('Pertanyaan umum &amp; panduan lengkap', false);
    }

    public function test_kode_bukti_unik_tanpa_karakter_ambigu(): void
    {
        $layanan = app(\App\Services\LayananPemilihan::class);
        $kode = collect(range(1, 200))->map(fn () => $layanan->buatKodeBukti());

        $this->assertSame(200, $kode->unique()->count());
        $kode->each(fn ($k) => $this->assertMatchesRegularExpression('/^OSIM-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}$/', $k));
        $this->assertDoesNotMatchRegularExpression('/[01ILO]/', $kode->map(fn ($k) => substr($k, 5))->implode(''));
    }

    public function test_tabrakan_kode_bukti_diterbitkan_ulang_bukan_dianggap_sudah_memilih(): void
    {
        $election = Candidate::find($this->idNomor(1))->election_id;
        $lain = Voter::factory()->create();
        Vote::create(['election_id' => $election, 'candidate_id' => $this->idNomor(1), 'voter_id' => $lain->id, 'kode_bukti' => 'OSIM-AAAA-AAAA']);

        // Kode pertama bertabrakan (meniru dua permintaan bersamaan), kode kedua unik.
        $layanan = \Mockery::mock(\App\Services\LayananPemilihan::class)->makePartial();
        $layanan->shouldReceive('buatKodeBukti')->andReturn('OSIM-AAAA-AAAA', 'OSIM-BBBB-CCCC');

        $suara = $layanan->kirimSuara($this->pemilih, \App\Models\Election::find($election), $this->idNomor(2));

        $this->assertSame('OSIM-BBBB-CCCC', $suara->kode_bukti);
        $this->assertSame(2, Vote::count());
    }

    public function test_penolakan_suara_ganda_berlapis_tetap_berjalan(): void
    {
        $layanan = app(\App\Services\LayananPemilihan::class);
        $pemilihan = \App\Models\Election::find(Candidate::find($this->idNomor(1))->election_id);

        $layanan->kirimSuara($this->pemilih, $pemilihan, $this->idNomor(1));

        // Lapis 1: kelayakan di server.
        try {
            $layanan->kirimSuara($this->pemilih, $pemilihan, $this->idNomor(2));
            $this->fail('Suara kedua seharusnya ditolak.');
        } catch (\App\Exceptions\SuaraDitolak $e) {
            $this->assertSame('sudah_memilih', $e->alasan);
        }

        // Lapis 2: indeks unik basis data (meniru dua permintaan bersamaan yang lolos cek kelayakan).
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        Vote::create(['election_id' => $pemilihan->id, 'voter_id' => $this->pemilih->id, 'candidate_id' => $this->idNomor(3), 'kode_bukti' => 'OSIM-ZZZZ-ZZZZ']);
    }

    public function test_tabrakan_karena_pemilih_sama_tetap_ditolak_tanpa_mencoba_ulang(): void
    {
        $pemilihan = \App\Models\Election::find(Candidate::find($this->idNomor(1))->election_id);
        $layanan = \Mockery::mock(\App\Services\LayananPemilihan::class)->makePartial();
        // Kelayakan "lolos" (seolah permintaan bersamaan), tetapi suara pemilih ini sudah tersimpan.
        $layanan->shouldReceive('kelayakan')->andReturn(['boleh' => true, 'alasan' => null, 'pesan' => null]);
        Vote::create(['election_id' => $pemilihan->id, 'voter_id' => $this->pemilih->id, 'candidate_id' => $this->idNomor(1), 'kode_bukti' => 'OSIM-YYYY-YYYY']);

        try {
            $layanan->kirimSuara($this->pemilih, $pemilihan, $this->idNomor(2));
            $this->fail('Seharusnya ditolak.');
        } catch (\App\Exceptions\SuaraDitolak $e) {
            $this->assertSame('sudah_memilih', $e->alasan);
        }
        $this->assertSame(1, Vote::count());
    }

    public function test_data_bukti_suara_tanpa_pilihan(): void
    {
        $layanan = app(\App\Services\LayananPemilihan::class);
        $this->assertNull($layanan->buktiSuara($this->pemilih));

        $pemilihan = \App\Models\Election::find(Candidate::find($this->idNomor(2))->election_id);
        $suara = $layanan->kirimSuara($this->pemilih, $pemilihan, $this->idNomor(2));
        $bukti = $layanan->buktiSuara($this->pemilih);

        $this->assertSame(['kode', 'pemilihan', 'waktu'], array_keys($bukti));
        $this->assertSame($suara->kode_bukti, $bukti['kode']);
        $this->assertSame($pemilihan->judul, $bukti['pemilihan']);
        $this->assertArrayNotHasKey('candidate_id', $bukti);
    }
}
