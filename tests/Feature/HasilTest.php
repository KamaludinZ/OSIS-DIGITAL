<?php

namespace Tests\Feature;

use Database\Seeders\PemilihanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class HasilTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_hasil_menampilkan_ringkasan_dan_perolehan(): void
    {
        $this->seed(PemilihanSeeder::class);

        $this->get('/hasil?simulasi=publik')
            ->assertOk()
            ->assertSeeInOrder(['Suara masuk', '612', 'Total pemilih', '840', 'Belum memilih', '228', 'Partisipasi pemilih', '72,9%'])
            ->assertSeeInOrder(['Rizky Pratama', '35%', '214 suara', 'Nadia Kusuma', '41%', '251 suara', 'Dimas Aditya', '24%', '147 suara'])
            ->assertSee('Lihat sebagai tabel')
            ->assertSee('width: max(41%, 2px)', false);
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

    public function test_panel_rekap_bisa_dimuat_ulang_dan_polling_saat_berjalan(): void
    {
        $this->seed(PemilihanSeeder::class);
        $this->travelTo(now()->setTime(10, 0, 0));

        $this->get('/hasil')
            ->assertSee('Rekap Suara Masuk')
            ->assertSee('wire:poll.30s="muatUlang"', false)
            ->assertSee('Partisipasi pemilih');

        $komponen = Volt::test('hasil')->assertSee('10:00:00');
        $this->travel(5)->minutes();
        $komponen->call('muatUlang')->assertSee('10:05:00');

        $this->get('/hasil?simulasi=selesai')->assertDontSee('wire:poll', false)->assertSee('Pemilihan ditutup');
    }

    public function test_pengumuman_pemenang_hanya_setelah_ditutup(): void
    {
        $this->seed(PemilihanSeeder::class);

        $this->get('/hasil')->assertDontSee('Ketua &amp; Wakil Ketua OSIM Terpilih', false);

        $this->get('/hasil?simulasi=publik')
            ->assertSee('Ketua &amp; Wakil Ketua OSIM Terpilih', false)
            ->assertSeeInOrder(['Terpilih', 'Nadia Kusuma &amp; Fajar Ramadhan', '251 suara (41%)'], false);
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

    public function test_pesan_menunggu_saat_hasil_belum_dipublikasikan(): void
    {
        $this->seed(PemilihanSeeder::class);

        $this->get('/hasil')
            ->assertOk()
            ->assertSee('Pemilihan berlangsung')
            ->assertSee('Hasil belum bisa dilihat')
            ->assertSee('Partisipasi pemilih')
            ->assertDontSee('Perolehan Suara')
            ->assertDontSee('251 suara');

        $this->get('/hasil?simulasi=selesai')
            ->assertSee('Menunggu pengumuman resmi')
            ->assertDontSee('Perolehan Suara')
            ->assertDontSee('Terpilih');
    }

    public function test_unduh_laporan_csv_setelah_hasil_dipublikasikan(): void
    {
        $this->seed(PemilihanSeeder::class);

        $this->get('/hasil')->assertDontSee('Unduh laporan');
        $this->get('/hasil?simulasi=publik')->assertSee('Unduh laporan')->assertSee('Cetak / simpan PDF');

        Volt::test('hasil')
            ->set('simulasi', 'publik')
            ->call('muatUlang')
            ->call('unduhCsv')
            ->assertFileDownloaded('laporan-hasil-pemilihan-ketua-osim-20262027.csv');

        Volt::test('hasil')->call('unduhCsv')->assertNoFileDownloaded();
    }
}
