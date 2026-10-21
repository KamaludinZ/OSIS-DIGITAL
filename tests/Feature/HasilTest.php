<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\User;
use App\Models\Vote;
use Database\Seeders\PemilihanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;
use Tests\TestCase;

class HasilTest extends TestCase
{
    use RefreshDatabase;

    /** Suara contoh: paslon 1 = 3, paslon 2 = 5, paslon 3 = 2, plus 2 siswa belum memilih. */
    private function siapkanSuara(): Election
    {
        $this->seed(PemilihanSeeder::class);
        $e = Election::firstOrFail();
        foreach ([1 => 3, 2 => 5, 3 => 2] as $nomor => $jumlah) {
            $calon = Candidate::where('nomor_urut', $nomor)->first();
            \App\Models\Voter::factory()->count($jumlah)->create()->each(fn ($u) => Vote::create([
                'election_id' => $e->id, 'voter_id' => $u->id, 'candidate_id' => $calon->id, 'kode_bukti' => 'OSIM-'.Str::upper(Str::random(9)),
            ]));
        }
        \App\Models\Voter::factory()->count(2)->create();

        return $e;
    }

    public function test_hasil_publik_menampilkan_perolehan_dari_database(): void
    {
        $this->siapkanSuara()->update(['hasil_publik' => true]);

        $this->get('/hasil')
            ->assertOk()
            ->assertSeeInOrder(['Suara masuk', '10', 'Total pemilih', '12', 'Belum memilih', '2', 'Partisipasi pemilih', '83,3%'])
            ->assertSeeInOrder(['Rizky Pratama', '30%', '3 suara', 'Nadia Kusuma', '50%', '5 suara', 'Dimas Aditya', '20%', '2 suara']);
    }

    public function test_perolehan_tidak_bocor_ke_siswa_sebelum_dipublikasikan(): void
    {
        $this->siapkanSuara();
        $this->actingAs(User::factory()->create());

        $html = $this->get('/hasil')->assertOk()->assertSee('Hasil belum bisa dilihat')->assertSee('Partisipasi pemilih')->getContent();

        // Angka per kandidat tidak boleh ada di HTML maupun state Livewire.
        $this->assertStringNotContainsString('5 suara', $html);
        $this->assertStringNotContainsString('&quot;suara&quot;', $html);
        $this->get('/hasil/laporan.csv')->assertForbidden();
        $this->get('/hasil/laporan')->assertForbidden();
    }

    public function test_panitia_bisa_memantau_perolehan_sebelum_publik(): void
    {
        $this->siapkanSuara();
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));

        $this->get('/hasil')
            ->assertSee('hanya terlihat oleh panitia')
            ->assertSee('5 suara')
            ->assertDontSee('Ketua &amp; Wakil Ketua OSIM Terpilih', false);
    }

    public function test_pemenang_diumumkan_setelah_ditutup_dan_publik(): void
    {
        $this->siapkanSuara()->update(['status' => 'selesai', 'hasil_publik' => true]);

        $this->get('/hasil')
            ->assertSee('Pemilihan ditutup')
            ->assertDontSee('wire:poll', false)
            ->assertSeeInOrder(['Terpilih', 'Nadia Kusuma &amp; Fajar Ramadhan', '5 suara (50%)'], false);
    }

    public function test_menunggu_pengumuman_resmi_setelah_ditutup(): void
    {
        $this->siapkanSuara()->update(['status' => 'selesai']);

        $this->get('/hasil')->assertSee('Menunggu pengumuman resmi')->assertDontSee('Perolehan Suara');
    }

    public function test_rekap_dimuat_ulang_dan_polling_saat_berjalan(): void
    {
        $e = $this->siapkanSuara();
        $this->get('/hasil')->assertSee('wire:poll.10s.visible="muatUlang"', false)->assertSee('Pemilihan berlangsung');

        $komponen = Volt::test('hasil')->assertSet('rekap.suara_masuk', 10);

        // Suara yang masuk lewat bilik suara langsung membuang cache rekap.
        $pemilih = \App\Models\Voter::factory()->create();
        app(\App\Services\LayananPemilihan::class)->kirimSuara($pemilih, $e, Candidate::first()->id);
        $komponen->call('muatUlang')->assertSet('rekap.suara_masuk', 11);

        // Jalur lain yang mencatat surat suara juga langsung membuang cache.
        Vote::create(['election_id' => $e->id, 'voter_id' => \App\Models\Voter::factory()->create()->id, 'candidate_id' => Candidate::first()->id, 'kode_bukti' => 'OSIM-LANGSUNG-1']);
        $komponen->call('muatUlang')->assertSet('rekap.suara_masuk', 12);

        // Perubahan di luar model (mis. pemulihan manual) bertahan sampai cache kedaluwarsa.
        \Illuminate\Support\Facades\DB::table('vote_tallies')->where('candidate_id', Candidate::first()->id)->increment('jumlah');
        $komponen->call('muatUlang')->assertSet('rekap.suara_masuk', 12);
        $this->travel(\App\Services\LayananHasil::CACHE_DETIK + 1)->seconds();
        $komponen->call('muatUlang')->assertSet('rekap.suara_masuk', 13);
    }

    public function test_hasil_sebelum_bilik_dibuka_tidak_ditulis_ditutup(): void
    {
        $this->seed(PemilihanSeeder::class);
        Election::firstOrFail()->update(['mulai' => now()->addDays(4)->setTime(8, 0), 'selesai' => now()->addDays(4)->setTime(12, 0)]);

        $this->get('/hasil')->assertOk()
            ->assertSee('Pemilihan belum dibuka')
            ->assertSee('Bilik suara belum dibuka')
            ->assertDontSee('Pemilihan ditutup')
            ->assertDontSee('Pemilihan sudah ditutup');

        // Status draf juga "belum dibuka".
        Election::firstOrFail()->update(['status' => 'draft']);
        $this->get('/hasil')->assertSee('Pemilihan belum dibuka')->assertDontSee('Pemilihan ditutup');
    }

    public function test_laporan_csv_menetralkan_isi_berbentuk_rumus(): void
    {
        $this->siapkanSuara()->update(['status' => 'selesai', 'hasil_publik' => true]);
        Candidate::where('nomor_urut', 1)->update(['nama_ketua' => '=HYPERLINK("http://contoh.test")']);
        $this->actingAs(User::factory()->create());

        $isi = $this->get('/hasil/laporan.csv')->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $isi);
        $this->assertStringNotContainsString(';"=HYPERLINK', $isi);
    }

    public function test_laporan_csv_dan_berita_acara_bila_perolehan_terlihat(): void
    {
        $this->siapkanSuara()->update(['status' => 'selesai', 'hasil_publik' => true]);

        // Tamu tetap melihat hasil, tetapi unduhan laporan hanya untuk pengguna yang sudah masuk.
        $this->get('/hasil')->assertOk()->assertDontSee('Unduh laporan')->assertDontSee(route('hasil.laporan.csv'));
        $this->get('/hasil/laporan.csv')->assertRedirect(route('login'));
        $this->get('/hasil/laporan')->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create());
        $this->get('/hasil')->assertSee('Unduh laporan')->assertSee(route('hasil.laporan.csv'))->assertSee(route('hasil.laporan'));

        $csv = $this->get('/hasil/laporan.csv');
        $csv->assertOk()->assertDownload('laporan-hasil-pemilihan-ketua-osim-20262027.csv');
        $isi = $csv->streamedContent();
        $baris = array_map(fn ($b) => str_getcsv($b, ';'), preg_split('/\R/', trim(ltrim($isi, "\xEF\xBB\xBF"))));
        $this->assertContains(['Total pemilih', '12'], $baris);
        $this->assertContains(['Suara masuk', '10'], $baris);
        $this->assertContains(['2', 'Nadia Kusuma', 'Fajar Ramadhan', '5', '50'], $baris);
        $this->assertContains(['Hasil', 'Terpilih: No. 2 Nadia Kusuma & Fajar Ramadhan'], $baris);

        $this->get('/hasil/laporan')
            ->assertOk()
            ->assertSee('Berita Acara Hasil Pemilihan')
            ->assertSee('Ketua Panitia Pemilihan')
            ->assertSeeInOrder(['Nadia Kusuma', '5', '50'])
            ->assertSee('memperoleh suara terbanyak');
    }

    public function test_panitia_bisa_mengunduh_laporan_sebelum_publik(): void
    {
        $this->siapkanSuara();
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));

        $this->get('/hasil/laporan.csv')->assertOk();
        $this->get('/hasil/laporan')->assertOk()->assertSee('hasil belum dipublikasikan');
    }

    public function test_hasil_kosong_tanpa_pemilihan(): void
    {
        $this->get('/hasil')->assertOk()->assertSee('Belum ada hasil pemilihan');
    }

    public function test_label_grafik_untuk_desimal_dan_nol_suara(): void
    {
        $kandidat = [
            ['id' => 1, 'nomor_urut' => 1, 'nama_ketua' => 'A', 'nama_wakil' => 'B', 'suara' => 1],
            ['id' => 2, 'nomor_urut' => 2, 'nama_ketua' => 'C', 'nama_wakil' => 'D', 'suara' => 2],
        ];

        $this->blade('<x-grafik-perolehan :kandidat="$k" :suara-masuk="3" />', ['k' => $kandidat])
            ->assertSeeInOrder(['33,3%', '1 suara', '66,7%', '2 suara']);

        $kosong = array_map(fn ($c) => ['suara' => 0] + $c, $kandidat);
        $this->blade('<x-grafik-perolehan :kandidat="$k" :suara-masuk="0" />', ['k' => $kosong])
            ->assertSee('0%')
            ->assertSee('Belum ada suara masuk.');
    }

    public function test_pemenang_seri_tidak_diumumkan(): void
    {
        $kandidat = [
            ['id' => 1, 'nomor_urut' => 1, 'nama_ketua' => 'A', 'nama_wakil' => 'B', 'foto_url' => null, 'suara' => 10],
            ['id' => 2, 'nomor_urut' => 2, 'nama_ketua' => 'C', 'nama_wakil' => 'D', 'foto_url' => null, 'suara' => 10],
        ];

        $this->blade('<x-pengumuman-pemenang :kandidat="$k" :suara-masuk="20" />', ['k' => $kandidat])
            ->assertSee('Perolehan suara teratas seri')
            ->assertDontSee('Terpilih');
    }

    public function test_pemenang_dari_layanan_termasuk_seri_dan_kosong(): void
    {
        $e = $this->siapkanSuara();
        $layanan = app(\App\Services\LayananHasil::class);

        $hasil = $layanan->pemenang($e);
        $this->assertSame('menang', $hasil['status']);
        $this->assertSame('Nadia Kusuma', $hasil['kandidat']['nama_ketua']);
        $this->assertSame(10, $hasil['suara_masuk']);

        // Paslon 1 menyusul jadi 5 suara -> seri.
        $satu = Candidate::where('nomor_urut', 1)->first();
        \App\Models\Voter::factory()->count(2)->create()->each(fn ($u) => Vote::create(['election_id' => $e->id, 'voter_id' => $u->id, 'candidate_id' => $satu->id, 'kode_bukti' => 'OSIM-'.Str::upper(Str::random(9))]));
        $this->assertSame('seri', $layanan->pemenang($e)['status']);

        $kosong = Election::factory()->create();
        Candidate::factory()->for($kosong)->create();
        $this->assertSame('kosong', $layanan->pemenang($kosong)['status']);
    }

    public function test_panitia_mempublikasikan_hasil_hanya_setelah_ditutup(): void
    {
        $e = $this->siapkanSuara();
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));

        // Masih berjalan: tidak boleh dipublikasikan.
        Volt::test('hasil')->call('aturPublikasi', true)->assertHasErrors('publikasi');
        $this->assertFalse($e->fresh()->hasil_publik);

        $e->update(['status' => 'selesai']);
        $this->get('/hasil')->assertSee('Publikasikan hasil');

        Volt::test('hasil')->call('aturPublikasi', true)->assertHasNoErrors()->assertSee('Ketua &amp; Wakil Ketua OSIM Terpilih', false);
        $this->assertTrue($e->fresh()->hasil_publik);

        Volt::test('hasil')->call('aturPublikasi', false);
        $this->assertFalse($e->fresh()->hasil_publik);
    }

    public function test_siswa_tidak_bisa_mempublikasikan_hasil(): void
    {
        $e = $this->siapkanSuara();
        $e->update(['status' => 'selesai']);
        $this->actingAs(User::factory()->create());

        $this->get('/hasil')->assertDontSee('Publikasikan hasil');
        Volt::test('hasil')->call('aturPublikasi', true)->assertForbidden();
        $this->assertFalse($e->fresh()->hasil_publik);
    }
}
