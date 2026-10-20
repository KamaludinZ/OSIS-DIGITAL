<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\Vote;
use App\Models\Voter;
use App\Services\SesiPemilih;
use Database\Seeders\PemilihanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BilikSuaraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PemilihanSeeder::class);
        $this->pemilih = Voter::factory()->berkode('246810')->create(['nama' => 'Budi Santoso', 'kelas' => 'VII-C']);
    }

    private Voter $pemilih;

    /** Masuk bilik suara sebagai pemilih (setara memasukkan kode pilih). */
    private function masuk(?Voter $pemilih = null): static
    {
        return $this->withSession([SesiPemilih::KUNCI_PEMILIH => ($pemilih ?? $this->pemilih)->id, 'pemilih_masuk_pada' => now()->timestamp]);
    }

    /** ID kandidat berdasarkan nomor urut pada data contoh. */
    private function idNomor(int $nomor): int
    {
        return Candidate::where('nomor_urut', $nomor)->value('id');
    }

    public function test_bilik_suara_menampilkan_daftar_kandidat_berurutan(): void
    {
        $this->masuk()->withSession(['panduan_dilewati' => true])->get('/bilik-suara')
            ->assertOk()
            ->assertSeeInOrder(['Pasangan Calon 1', 'Pasangan Calon 2', 'Pasangan Calon 3'])
            ->assertSee('Foto calon belum tersedia');
    }

    public function test_profil_kandidat_menampilkan_visi_dan_misi(): void
    {
        $this->get('/kandidat/'.$this->idNomor(2))
            ->assertOk()
            ->assertSee('Nadia Kusuma')
            ->assertSeeInOrder(['Visi', 'Misi', 'Membentuk kelompok belajar sebaya']);
    }

    public function test_profil_kandidat_tidak_ditemukan(): void
    {
        $this->get('/kandidat/9999')->assertNotFound();
    }

    public function test_pesan_saat_pemilihan_belum_dibuka(): void
    {
        Election::query()->update(['mulai' => now()->addDay(), 'selesai' => now()->addDays(2)]);

        $this->get('/bilik-suara')
            ->assertOk()
            ->assertSee('Pemilihan belum dibuka')
            ->assertSee('Kenali Pasangan Calon');
    }

    public function test_pesan_saat_pemilihan_ditutup_atau_tidak_ada(): void
    {
        Election::query()->update(['status' => 'selesai']);
        $this->get('/bilik-suara')->assertOk()->assertSee('Pemilihan sudah ditutup')->assertDontSee('Pasangan Calon 1');

        Election::query()->delete();
        $this->get('/bilik-suara')->assertOk()->assertSee('Belum ada pemilihan');
    }

    public function test_pemilih_masuk_dengan_kode_6_digit_tanpa_login(): void
    {
        $this->withSession(['panduan_dilewati' => true])->get('/bilik-suara')
            ->assertOk()
            ->assertSee('Masukkan kode pilihmu')
            ->assertDontSee('Pilih nomor 1');

        Volt::test('bilik-suara')
            ->call('tutupPanduan')
            ->set('kode', '000000')
            ->call('masukKode')
            ->assertHasErrors('kode')
            ->assertSee('Kode pilih tidak dikenal')
            ->set('kode', '246810')
            ->call('masukKode')
            ->assertHasNoErrors()
            ->assertSee('Memilih sebagai')
            ->assertSee('Budi Santoso')
            ->assertSee('Pilih nomor 1')
            ->call('keluar')
            ->assertSee('Masukkan kode pilihmu');
    }

    public function test_tebakan_kode_dibatasi(): void
    {
        $komponen = Volt::test('bilik-suara')->call('tutupPanduan');
        foreach (range(1, 10) as $i) {
            $komponen->set('kode', str_pad((string) $i, 6, '9', STR_PAD_LEFT))->call('masukKode');
        }

        $komponen->set('kode', '246810')->call('masukKode')->assertHasErrors('kode')->assertSee('Terlalu banyak percobaan');
    }

    public function test_hanya_satu_kandidat_yang_aktif_dipilih(): void
    {
        session([SesiPemilih::KUNCI_PEMILIH => $this->pemilih->id, 'pemilih_masuk_pada' => now()->timestamp]);

        Volt::test('bilik-suara')->call('tutupPanduan')
            ->assertSet('dipilih', null)
            ->call('pilih', $this->idNomor(2))
            ->assertSet('dipilih', $this->idNomor(2))
            ->assertSee('Nomor 2 — Nadia Kusuma')
            ->call('pilih', $this->idNomor(3))
            ->assertSet('dipilih', $this->idNomor(3))
            ->call('pilih', $this->idNomor(3))
            ->assertSet('dipilih', null)
            ->call('pilih', 9999)
            ->assertSet('dipilih', null);
    }

    public function test_konfirmasi_lalu_kirim_suara_tersimpan_sekali(): void
    {
        session([SesiPemilih::KUNCI_PEMILIH => $this->pemilih->id, 'pemilih_masuk_pada' => now()->timestamp]);

        Volt::test('bilik-suara')->call('tutupPanduan')
            ->call('bukaKonfirmasi')
            ->assertHasErrors('dipilih')
            ->assertSee('Pilih salah satu pasangan calon terlebih dahulu.')
            ->call('pilih', $this->idNomor(2))
            ->call('bukaKonfirmasi')
            ->assertSet('konfirmasi', true)
            ->assertSee('Apakah Anda yakin memilih')
            ->call('kirimSuara')
            ->assertSet('terkirim', true)
            ->assertRedirect(route('bilik-suara.bukti'));

        // Kehadiran tercatat tanpa pilihan; pilihan tercatat anonim di ballots.
        $this->assertDatabaseHas('votes', ['voter_id' => $this->pemilih->id]);
        $this->assertDatabaseHas('ballots', ['candidate_id' => $this->idNomor(2)]);
        $this->assertMatchesRegularExpression('/^OSIM-[A-Z0-9]{4}-[A-Z0-9]{4}$/', Vote::value('kode_bukti'));

        // Kode yang sama tidak bisa dipakai memilih lagi.
        Volt::test('bilik-suara')->call('tutupPanduan')
            ->assertSee('Anda sudah menggunakan hak pilih')
            ->call('pilih', $this->idNomor(1))
            ->assertSet('dipilih', null);
        $this->assertSame(1, Vote::count());
    }

    public function test_pemilih_belum_terverifikasi_tidak_bisa_memilih(): void
    {
        $menunggu = Voter::factory()->berkode('111222')->create(['verifikasi' => 'menunggu']);
        session([SesiPemilih::KUNCI_PEMILIH => $menunggu->id, 'pemilih_masuk_pada' => now()->timestamp]);

        Volt::test('bilik-suara')->call('tutupPanduan')
            ->assertSee('masih menunggu verifikasi panitia')
            ->call('pilih', $this->idNomor(1))
            ->assertSet('dipilih', null);
    }

    public function test_kelayakan_dicek_ulang_saat_kirim_suara(): void
    {
        session([SesiPemilih::KUNCI_PEMILIH => $this->pemilih->id, 'pemilih_masuk_pada' => now()->timestamp]);
        $komponen = Volt::test('bilik-suara')->call('tutupPanduan')->call('pilih', $this->idNomor(1))->call('bukaKonfirmasi');

        Election::query()->update(['status' => 'selesai']);

        $komponen->call('kirimSuara')->assertSet('terkirim', false)->assertHasErrors('dipilih');
    }

    public function test_bukti_suara_dan_selesai_mengakhiri_sesi(): void
    {
        Vote::create(['election_id' => Election::value('id'), 'voter_id' => $this->pemilih->id, 'candidate_id' => $this->idNomor(2), 'kode_bukti' => 'OSIM-ABCD-EFGH']);

        $this->masuk()->get('/bilik-suara/bukti')
            ->assertOk()
            ->assertSee('Suara Anda sudah tercatat')
            ->assertSee('OSIM-ABCD-EFGH')
            ->assertSee('Stempel: SUDAH MEMILIH')
            ->assertSee('Selesai &amp; keluar', false)
            ->assertSee('Cetak bukti')
            ->assertSee('window.print()', false)
            ->assertSeeInOrder(['Kembali', 'Selesai'])
            ->assertDontSee('Nadia Kusuma');

        session([SesiPemilih::KUNCI_PEMILIH => $this->pemilih->id, 'pemilih_masuk_pada' => now()->timestamp]);
        Volt::test('bilik-suara.bukti')->call('selesai')->assertRedirect(route('home'));
        $this->assertNull(session(SesiPemilih::KUNCI_PEMILIH));
    }

    public function test_bukti_tanpa_sesi_menampilkan_keadaan_kosong(): void
    {
        $this->get('/bilik-suara/bukti')->assertOk()
            ->assertSee('Belum ada bukti suara')
            ->assertSee('href="'.route('bilik-suara').'"', false)
            ->assertSee('Kembali ke Beranda')
            ->assertDontSee('Kartu Bukti Memilih');

        // Sudah masuk tapi belum memilih: tetap keadaan kosong.
        $this->masuk()->get('/bilik-suara/bukti')->assertOk()->assertSee('Belum ada bukti suara');
    }

    public function test_tombol_beranda_berubah_setelah_memilih(): void
    {
        $this->masuk()->get('/')->assertOk()->assertSee('Mulai Memilih')->assertDontSee('Anda sudah memilih');

        app(\App\Services\LayananPemilihan::class)->kirimSuara($this->pemilih, Election::first(), $this->idNomor(1));

        $this->masuk()->get('/')->assertOk()->assertSee('Anda sudah memilih')->assertDontSee('Mulai Memilih');
    }

    public function test_panduan_tampil_pertama_kali_dan_bisa_dilewati(): void
    {
        $this->get('/bilik-suara')->assertOk()->assertSee('Cara memilih')->assertSee('Lewati');

        Volt::test('bilik-suara')
            ->assertSet('panduan', true)
            ->call('tutupPanduan')
            ->assertSet('panduan', false)
            ->assertSee('Masukkan kode pilihmu')
            ->call('bukaPanduan')
            ->assertSet('panduan', true);

        $this->get('/bilik-suara')->assertOk()->assertDontSee('Mengerti, mulai memilih');
    }

    public function test_layanan_menolak_suara_ganda_dan_kandidat_asing(): void
    {
        $layanan = app(\App\Services\LayananPemilihan::class);
        $pemilihan = Election::first();
        $asing = Candidate::factory()->create();

        try {
            $layanan->kirimSuara($this->pemilih, $pemilihan, $asing->id);
            $this->fail('Kandidat dari pemilihan lain seharusnya ditolak.');
        } catch (\App\Exceptions\SuaraDitolak $e) {
            $this->assertSame('kandidat_tidak_valid', $e->alasan);
        }

        $layanan->kirimSuara($this->pemilih, $pemilihan, $this->idNomor(1));

        $this->expectException(\App\Exceptions\SuaraDitolak::class);
        $layanan->kirimSuara($this->pemilih, $pemilihan, $this->idNomor(2));
    }
}
