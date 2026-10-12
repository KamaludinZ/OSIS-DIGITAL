<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Event;
use App\Models\User;
use App\Models\WorkProgram;
use App\Services\LayananPublikasi;
use Database\Seeders\PublikasiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BerandaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PublikasiSeeder::class);
    }

    public function test_beranda_menampilkan_bagian_utama(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(["Assalamu'alaikum", 'Pengumuman Terbaru', 'Agenda &amp; Kegiatan', 'Sorotan Program Kerja'], false)
            ->assertSee('Jadwal Pemilihan Ketua OSIM 2026/2027')
            ->assertSee('Bank Sampah Sekolah');
    }

    public function test_daftar_pengumuman_terbaru_diurutkan_dan_dibatasi(): void
    {
        $this->blade('<x-daftar-pengumuman :pengumuman="$p" :batas="2" />', ['p' => app(LayananPublikasi::class)->pengumumanTerbaru()])
            ->assertSeeInOrder(['Jadwal Pemilihan Ketua OSIM', 'Pendaftaran Lomba Kebersihan Kelas'])
            ->assertDontSee('Hasil Rapat Pleno');

        $this->blade('<x-daftar-pengumuman :pengumuman="[]" />')->assertSee('Belum ada pengumuman');
    }

    public function test_agenda_hanya_menampilkan_kegiatan_mendatang(): void
    {
        $layanan = app(LayananPublikasi::class);
        $agenda = [
            $layanan->cariKegiatan(Event::where('nama', 'Debat Kandidat Ketua OSIM')->value('id')),
            ...$layanan->agendaMendatang(),
        ];

        $this->blade('<x-agenda-mendatang :agenda="$a" />', ['a' => $agenda])
            ->assertDontSee('Debat Kandidat')
            ->assertSeeInOrder(['Pemungutan Suara Ketua OSIM', 'Hari ini', 'Lomba Kebersihan Kelas', 'Pentas Seni Tahunan']);

        $this->blade('<x-agenda-mendatang :agenda="[]" />')->assertSee('Belum ada kegiatan terjadwal.');
    }

    public function test_sorotan_hanya_program_kerja_unggulan(): void
    {
        $program = [
            ...app(LayananPublikasi::class)->programKerjaUnggulan(),
            ['id' => 99, 'nama' => 'Turnamen Futsal Antarkelas', 'deskripsi' => '-', 'divisi' => 'Divisi Olahraga', 'status' => 'rencana', 'unggulan' => false, 'capaian' => null],
        ];

        $this->blade('<x-sorotan-program-kerja :program-kerja="$p" />', ['p' => $program])
            ->assertSee('Bank Sampah Sekolah')
            ->assertSee('320 kg sampah terkumpul')
            ->assertSee('Selesai')
            ->assertDontSee('Turnamen Futsal');
    }

    public function test_pencarian_kabar_osis(): void
    {
        $this->get('/cari?q=lomba')
            ->assertOk()
            ->assertSee('Pendaftaran Lomba Kebersihan Kelas')
            ->assertSee('Lomba Kebersihan Kelas')
            ->assertDontSee('Peringatan Sumpah Pemuda');

        Volt::test('cari')
            ->set('kataKunci', 'PEMILIHAN')
            ->assertSee('Jadwal Pemilihan Ketua OSIM 2026/2027')
            ->assertSee('Debat Kandidat Ketua OSIM')
            ->assertDontSee('Bank Sampah');
    }

    public function test_halaman_detail_pengumuman_dan_kegiatan(): void
    {
        $this->get('/')->assertSee(route('pengumuman.detail', Announcement::where('judul', 'like', 'Jadwal Pemilihan%')->value('id')))->assertSee(route('kegiatan.detail', Event::where('nama', 'Pemungutan Suara Ketua OSIM')->value('id')));

        $lomba = Announcement::where('judul', 'Pendaftaran Lomba Kebersihan Kelas')->value('id');
        $this->get('/pengumuman/'.$lomba)->assertOk()->assertSee('Pendaftaran Lomba Kebersihan Kelas')->assertSee('Oleh Divisi Lingkungan');
        $this->get('/kegiatan/'.Event::where('nama', 'Pentas Seni Tahunan')->value('id'))->assertOk()->assertSee('Pentas Seni Tahunan')->assertSee('Aula Utama');
        $this->get('/pengumuman/9999')->assertNotFound();
        $this->get('/kegiatan/9999')->assertNotFound();
    }

    public function test_hanya_pengumuman_terpublikasi_yang_tampil(): void
    {
        $draf = Announcement::factory()->draf()->create(['judul' => 'Rahasia Draf Panitia']);
        $terjadwal = Announcement::factory()->create(['judul' => 'Pengumuman Minggu Depan', 'dipublikasikan_pada' => now()->addWeek()]);
        Announcement::factory()->create(['judul' => 'Kabar Paling Baru', 'ringkasan' => null, 'isi' => 'Isi lengkap kabar paling baru.', 'dipublikasikan_pada' => now()]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Kabar Paling Baru', 'Isi lengkap kabar paling baru.', 'Jadwal Pemilihan'])
            ->assertDontSee('Rahasia Draf Panitia')
            ->assertDontSee('Pengumuman Minggu Depan')
            ->assertDontSee('Perubahan Jadwal Upacara'); // dibatasi 4 terbaru

        $this->get('/pengumuman/'.$draf->id)->assertNotFound();
        $this->get('/pengumuman/'.$terjadwal->id)->assertNotFound();
    }

    public function test_beranda_menampilkan_agenda_mendatang_dari_database(): void
    {
        Event::factory()->create(['nama' => 'Rapat Kerja Akhir Tahun', 'tanggal_mulai' => today()->addDays(2), 'tanggal_selesai' => today()->addDays(2)]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Pemungutan Suara Ketua OSIM', 'Hari ini', 'Rapat Kerja Akhir Tahun', 'Lomba Kebersihan Kelas', 'Peringatan Sumpah Pemuda'])
            ->assertDontSee('Debat Kandidat Ketua OSIM')
            ->assertDontSee('Pentas Seni Tahunan'); // dibatasi 4 kegiatan

        // Kegiatan yang sudah lewat tetap bisa dibuka dari arsip/pencarian.
        $this->get('/kegiatan/'.Event::where('nama', 'Debat Kandidat Ketua OSIM')->value('id'))->assertOk()->assertSee('Sudah berlalu');
    }

    public function test_sorotan_program_kerja_unggulan_dari_database(): void
    {
        WorkProgram::factory()->create(['nama' => 'Program Biasa Tersembunyi', 'unggulan' => false]);
        WorkProgram::factory()->create(['nama' => 'Literasi Pagi', 'unggulan' => true, 'status' => 'rencana']);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Bank Sampah Sekolah', 'Kotak Aspirasi Digital', 'Kelas Inspirasi Alumni', 'Literasi Pagi'])
            ->assertSee('87 aspirasi dijawab')
            ->assertDontSee('Program Biasa Tersembunyi')
            ->assertDontSee('Turnamen Futsal Antarkelas');
    }

    public function test_pencarian_dari_database_tidak_peka_huruf_dan_aman(): void
    {
        Announcement::factory()->draf()->create(['judul' => 'Draf Lomba Rahasia']);

        Volt::test('cari')
            ->set('kataKunci', 'LOMBA')
            ->assertSee('Pendaftaran Lomba Kebersihan Kelas')
            ->assertSee('Lomba Kebersihan Kelas')
            ->assertDontSee('Draf Lomba Rahasia');

        // Cari lewat nama divisi penanggung jawab.
        Volt::test('cari')->set('kataKunci', 'seni & budaya')->assertSee('Pentas Seni Tahunan');

        // Karakter wildcard SQL diperlakukan sebagai teks biasa.
        Volt::test('cari')->set('kataKunci', '%%')->assertSee('0 hasil');
    }

    public function test_detail_pengumuman_memecah_paragraf_dan_nama_penulis(): void
    {
        $pengurus = User::factory()->create(['name' => 'Rina Pengurus']);
        $item = Announcement::factory()->create([
            'judul' => 'Rapat Akbar',
            'isi' => "Paragraf pertama.\n\nParagraf kedua.\r\n\r\nParagraf ketiga.",
            'penulis_label' => null,
            'penulis_id' => $pengurus->id,
        ]);

        $detail = app(LayananPublikasi::class)->cariPengumuman($item->id);

        $this->assertSame(['Paragraf pertama.', 'Paragraf kedua.', 'Paragraf ketiga.'], $detail['isi']);
        $this->assertSame('Rina Pengurus', $detail['penulis']);

        $this->get('/pengumuman/'.$item->id)
            ->assertOk()
            ->assertSee('<title>Rapat Akbar · OSIM MTsN 2 Kota Malang</title>', false)
            ->assertSeeInOrder(['Paragraf pertama.', 'Paragraf kedua.', 'Paragraf ketiga.'])
            ->assertSee('Oleh Rina Pengurus');
    }

    public function test_detail_kegiatan_menampilkan_divisi_dan_status(): void
    {
        $id = Event::where('nama', 'Lomba Kebersihan Kelas')->value('id');

        $this->get('/kegiatan/'.$id)
            ->assertOk()
            ->assertSee('Akan datang')
            ->assertSee('Divisi Lingkungan')
            ->assertSee('Seluruh kelas')
            ->assertSee('diselenggarakan oleh Divisi Lingkungan');
    }
}
