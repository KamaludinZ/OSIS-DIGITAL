<?php

namespace Tests\Feature;

use App\Models\Election;
use App\Models\Candidate;
use App\Services\LayananPemilihan;
use Database\Seeders\PemilihanSeeder;
use Livewire\Volt\Volt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KandidatTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_daftar_pasangan_calon(): void
    {
        $this->seed(PemilihanSeeder::class);

        $this->get('/kandidat')
            ->assertOk()
            ->assertSee('Kenali Pasangan Calon')
            ->assertSeeInOrder(['Pasangan Calon 1', 'Pasangan Calon 2', 'Pasangan Calon 3'])
            ->assertSee('Sekolah yang hijau dan ruang kreatif bagi setiap siswa.')
            ->assertSee('Mulai Memilih');
    }

    public function test_daftar_kosong_bila_belum_ada_kandidat(): void
    {
        Election::factory()->create();

        $this->get('/kandidat')->assertOk()->assertSee('Belum ada pasangan calon');
    }

    public function test_detail_profil_dengan_tab_dan_navigasi_paslon(): void
    {
        $this->seed(PemilihanSeeder::class);
        $id = fn (int $n) => Candidate::where('nomor_urut', $n)->value('id');

        $this->get('/kandidat/'.$id(2))
            ->assertOk()
            ->assertSeeInOrder(['Profil &amp; Visi Misi', 'Materi Kampanye', 'Jadwal Kampanye', 'Tanya Jawab'], false)
            ->assertSee('Membentuk kelompok belajar sebaya')
            ->assertSee(route('kandidat.profil', $id(1)))
            ->assertSee(route('kandidat.profil', $id(3)))
            ->assertSee('Mulai Memilih');

        $this->get('/kandidat/'.$id(1))->assertDontSee('Paslon 0')->assertSee('Paslon 2');

        Volt::test('kandidat.profil', ['id' => $id(1)])
            ->set('tab', 'jadwal')
            ->assertDontSee('Latar Belakang')
            ->assertSee('Jadwal Kampanye');

        $this->get('/kandidat/'.$id(1).'?tab=ngawur')->assertOk()->assertSee('Latar Belakang');
    }

    public function test_tab_materi_kampanye_menampilkan_janji_poster_dan_video(): void
    {
        $this->seed([PemilihanSeeder::class, \Database\Seeders\KampanyeSeeder::class]);
        $id = Candidate::where('nomor_urut', 3)->value('id');

        $this->get('/kandidat/'.$id.'?tab=materi')
            ->assertOk()
            ->assertSeeInOrder(['Janji Kerja', 'Bank sampah di seluruh kelas.', 'Poster', 'Video'])
            ->assertSee('/images/kampanye/paslon-3-poster-1.svg')
            ->assertSee('Video belum tersedia');

        $this->blade('<x-materi-kampanye :materi="$m" />', ['m' => [
            ['id' => 1, 'tipe' => 'video', 'url_konten' => 'https://youtu.be/dQw4w9WgXcQ', 'deskripsi' => 'Video debat'],
        ]])->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);

        $this->blade('<x-materi-kampanye :materi="[]" />')->assertSee('Belum ada materi kampanye');
    }

    public function test_poster_dibuka_di_popup_dan_gambar_dilindungi(): void
    {
        $this->blade('<x-materi-kampanye :materi="$m" />', ['m' => [
            ['id' => 1, 'tipe' => 'poster', 'url_konten' => '/storage/kandidat/materi/a.png', 'deskripsi' => 'Poster utama'],
        ]])
            ->assertSee('aria-haspopup="dialog"', false)
            ->assertSee('<dialog', false)
            ->assertDontSee('target="_blank"', false)
            ->assertSee('osim-lindung', false)
            ->assertSee('draggable="false"', false);

        $this->blade('<x-osim.pasfoto foto="/storage/kandidat/a.png" alt="Foto" />')
            ->assertSee('osim-lindung', false)
            ->assertSee('draggable="false"', false);
    }

    public function test_jadwal_kampanye_terurut_dan_menandai_yang_lewat(): void
    {
        $this->travelTo(now()->setTime(11, 0));

        $jadwal = [
            ['id' => 1, 'tanggal' => today()->addDays(2)->toDateString(), 'waktu' => '10:00', 'tempat' => 'Kantin Sekolah'],
            ['id' => 2, 'tanggal' => today()->subDays(4)->toDateString(), 'waktu' => '09:30', 'tempat' => 'Aula Utama'],
            ['id' => 3, 'tanggal' => today()->toDateString(), 'waktu' => '12:15', 'tempat' => 'Masjid Sekolah'],
        ];

        $this->blade('<x-jadwal-kampanye :jadwal="$j" />', ['j' => $jadwal])
            ->assertSeeInOrder(['Aula Utama', 'Sudah lewat', 'Masjid Sekolah', 'Hari ini', 'Kantin Sekolah']);

        $this->blade('<x-jadwal-kampanye :jadwal="[]" />')->assertSee('Belum ada jadwal kampanye');

        $this->seed([PemilihanSeeder::class, \Database\Seeders\KampanyeSeeder::class]);
        $this->get('/kandidat/'.Candidate::where('nomor_urut', 2)->value('id').'?tab=jadwal')
            ->assertOk()
            ->assertSeeInOrder(['Lapangan Basket', 'Sudah lewat', 'Perpustakaan', 'Berikutnya']);
    }

    public function test_tanya_jawab_dan_form_pertanyaan(): void
    {
        $this->seed([PemilihanSeeder::class, \Database\Seeders\KampanyeSeeder::class]);
        $id = Candidate::where('nomor_urut', 1)->value('id');

        $this->get('/kandidat/'.$id.'?tab=tanya-jawab')
            ->assertOk()
            ->assertSee('Bagaimana memastikan aspirasi benar-benar ditindaklanjuti?')
            ->assertSee('Jawaban Paslon 1')
            ->assertSee('Menunggu jawaban')
            ->assertSee('Konfirmasi dulu identitasmu');

        $siswa = \App\Models\Voter::factory()->create(['nama' => 'Budi Penanya', 'kelas' => 'VII-C', 'nomor_induk' => '0091234501', 'tanggal_lahir' => '2012-08-17']);

        Volt::test('kandidat.profil', ['id' => $id])
            ->set('tab', 'tanya-jawab')
            ->set('nomorInduk', '0091234501')
            ->set('tanggalLahir', '2012-08-18')
            ->call('verifikasiPenanya')
            ->assertHasErrors('nomorInduk')
            ->assertSee('NISN dan tanggal lahir tidak cocok')
            ->set('nomorInduk', '0091234501')
            ->set('tanggalLahir', '2012-08-17')
            ->call('verifikasiPenanya')
            ->assertHasNoErrors()
            ->assertSee('Bertanya sebagai')
            ->set('pertanyaanBaru', 'pendek')
            ->call('kirimPertanyaan')
            ->assertHasErrors(['pertanyaanBaru' => 'min'])
            ->set('pertanyaanBaru', 'Kapan forum ketua kelas pertama diadakan?')
            ->call('kirimPertanyaan')
            ->assertHasNoErrors()
            ->assertSet('pertanyaanBaru', '')
            ->assertSeeInOrder(['Budi Penanya', 'VII-C', 'Kapan forum ketua kelas pertama diadakan?', 'Bagaimana memastikan aspirasi']);

        $this->assertDatabaseHas('candidate_questions', ['candidate_id' => $id, 'voter_id' => $siswa->id, 'pertanyaan' => 'Kapan forum ketua kelas pertama diadakan?', 'jawaban' => null]);
        $this->assertNotNull($siswa->fresh()->penanya_terverifikasi_pada);
    }

    public function test_gtk_bertanya_dengan_nik_dan_tanggal_lahir(): void
    {
        $this->seed(PemilihanSeeder::class);
        $id = Candidate::where('nomor_urut', 2)->value('id');
        \App\Models\Voter::factory()->gtk()->create(['nama' => 'Ibu Rahma', 'jabatan' => 'Guru Bahasa', 'nomor_induk' => '3573014101850001', 'tanggal_lahir' => '1985-01-01']);

        Volt::test('kandidat.profil', ['id' => $id])
            ->set('tab', 'tanya-jawab')
            ->set('jenisPenanya', 'gtk')
            ->assertSee('NIK')
            ->set('nomorInduk', '3573014101850001')
            ->set('tanggalLahir', '1985-01-01')
            ->call('verifikasiPenanya')
            ->assertHasNoErrors()
            ->set('pertanyaanBaru', 'Bagaimana program ini mendukung kegiatan belajar?')
            ->call('kirimPertanyaan')
            ->assertSeeInOrder(['Ibu Rahma', 'Guru Bahasa']);
    }

    public function test_verifikasi_penanya_dibatasi(): void
    {
        $this->seed(PemilihanSeeder::class);
        $komponen = Volt::test('kandidat.profil', ['id' => Candidate::first()->id])->set('tab', 'tanya-jawab');
        foreach (range(1, 5) as $i) {
            $komponen->set('nomorInduk', '00000000'.$i.$i)->set('tanggalLahir', '2012-01-01')->call('verifikasiPenanya');
        }
        $komponen->set('nomorInduk', '0000000099')->set('tanggalLahir', '2012-01-01')->call('verifikasiPenanya')->assertSee('Terlalu banyak percobaan');
    }

    public function test_tombol_kembali_mengikuti_halaman_asal(): void
    {
        $this->seed(PemilihanSeeder::class);
        $id = Candidate::where('nomor_urut', 2)->value('id');

        $this->get('/kandidat/'.$id)->assertSee('Semua kandidat')->assertSee(route('kandidat.index'));

        $this->get('/kandidat/'.$id.'?dari=bilik-suara')
            ->assertSee('Kembali ke Bilik Suara')
            ->assertSee(route('kandidat.profil', ['id' => Candidate::where('nomor_urut', 3)->value('id'), 'dari' => 'bilik-suara']), false);

        $this->get('/kandidat/'.$id.'?dari=https://jahat.example')->assertSee('Semua kandidat');

        $this->withSession(['panduan_dilewati' => true])->get('/bilik-suara')
            ->assertSee(route('kandidat.profil', ['id' => $id, 'dari' => 'bilik-suara']), false);
    }

    public function test_daftar_dan_detail_hanya_untuk_periode_aktif(): void
    {
        $lama = Election::factory()->selesai()->create(['judul' => 'Pemilihan Lama']);
        $calonLama = Candidate::factory()->for($lama)->create(['nomor_urut' => 1, 'nama_ketua' => 'Ketua Periode Lalu']);
        $this->seed(PemilihanSeeder::class);

        $layanan = app(LayananPemilihan::class);
        $this->assertSame([1, 2, 3], array_column($layanan->daftarKandidat(), 'nomor_urut'));
        $this->assertNotContains('Ketua Periode Lalu', array_column($layanan->daftarKandidat(), 'nama_ketua'));
        $this->assertNull($layanan->cariKandidat($calonLama->id));

        $this->get('/kandidat')->assertDontSee('Ketua Periode Lalu');
        $this->get('/kandidat/'.$calonLama->id)->assertNotFound();
    }

    public function test_materi_kampanye_dari_database_milik_paslon_sendiri(): void
    {
        $this->seed(PemilihanSeeder::class);
        [$satu, $dua] = Candidate::orderBy('nomor_urut')->take(2)->get()->all();

        \App\Models\CampaignMaterial::factory()->for($satu)->create(['tipe' => 'janji', 'deskripsi' => 'Janji kedua', 'urutan' => 2]);
        \App\Models\CampaignMaterial::factory()->for($satu)->create(['tipe' => 'janji', 'deskripsi' => 'Janji pertama', 'urutan' => 1]);
        \App\Models\CampaignMaterial::factory()->for($dua)->create(['tipe' => 'janji', 'deskripsi' => 'Milik paslon dua']);

        $materi = app(\App\Services\LayananKampanye::class)->materi($satu->id);
        $this->assertSame(['Janji pertama', 'Janji kedua'], array_column($materi, 'deskripsi'));

        $this->get('/kandidat/'.$satu->id.'?tab=materi')
            ->assertSeeInOrder(['Janji pertama', 'Janji kedua'])
            ->assertDontSee('Milik paslon dua')
            ->assertDontSee('Poster');

        $this->get('/kandidat/'.Candidate::where('nomor_urut', 3)->value('id').'?tab=materi')->assertSee('Belum ada materi kampanye');
    }

    public function test_jadwal_kampanye_dari_database(): void
    {
        $this->seed(PemilihanSeeder::class);
        $calon = Candidate::where('nomor_urut', 1)->first();
        \App\Models\CampaignSchedule::factory()->for($calon)->create(['tanggal' => today()->addDays(3), 'waktu' => '08:00:00', 'tempat' => 'Lapangan']);
        \App\Models\CampaignSchedule::factory()->for($calon)->create(['tanggal' => today()->addDay(), 'waktu' => '13:30:00', 'tempat' => 'Kantin']);

        $jadwal = app(\App\Services\LayananKampanye::class)->jadwal($calon->id);
        $this->assertSame(['Kantin', 'Lapangan'], array_column($jadwal, 'tempat'));
        $this->assertSame('13:30', $jadwal[0]['waktu']);

        $this->get('/kandidat/'.$calon->id.'?tab=jadwal')->assertSeeInOrder(['Kantin', 'pukul 13:30', 'Lapangan', 'pukul 08:00']);
    }

    public function test_tanya_jawab_tersimpan_dimoderasi_dan_dibatasi(): void
    {
        $this->seed(PemilihanSeeder::class);
        $calon = Candidate::where('nomor_urut', 3)->first();
        \App\Models\CandidateQuestion::factory()->for($calon)->create(['pertanyaan' => 'Pertanyaan spam yang disembunyikan', 'ditampilkan' => false]);

        $this->get('/kandidat/'.$calon->id.'?tab=tanya-jawab')->assertDontSee('Pertanyaan spam yang disembunyikan');

        $siswa = \App\Models\Voter::factory()->create(['nama' => 'Dewi', 'kelas' => 'IX-A']);
        session([\App\Services\SesiPemilih::KUNCI_PENANYA => $siswa->id]);

        $komponen = Volt::test('kandidat.profil', ['id' => $calon->id])->set('tab', 'tanya-jawab');
        foreach (range(1, 5) as $i) {
            $komponen->set('pertanyaanBaru', "Pertanyaan nomor {$i} untuk paslon tiga")->call('kirimPertanyaan')->assertHasNoErrors();
        }
        $komponen->set('pertanyaanBaru', 'Pertanyaan keenam yang harus ditolak')->call('kirimPertanyaan')->assertHasErrors('pertanyaanBaru');

        $this->assertSame(5, $calon->questions()->where('voter_id', $siswa->id)->count());
        $this->get('/kandidat/'.$calon->id.'?tab=tanya-jawab')->assertSee('Dewi · IX-A')->assertSee('Menunggu jawaban');
    }

    public function test_pertanyaan_tidak_dobel_saat_tombol_ditekan_berulang(): void
    {
        $this->seed(PemilihanSeeder::class);
        $calon = Candidate::where('nomor_urut', 2)->first();
        $siswa = \App\Models\Voter::factory()->create();
        session([\App\Services\SesiPemilih::KUNCI_PENANYA => $siswa->id]);

        $komponen = Volt::test('kandidat.profil', ['id' => $calon->id])->set('tab', 'tanya-jawab');
        foreach (range(1, 3) as $_) {
            $komponen->set('pertanyaanBaru', 'Kapan   kelompok belajar dimulai?')->call('kirimPertanyaan')->assertHasNoErrors();
        }

        $this->assertSame(1, $calon->questions()->where('voter_id', $siswa->id)->count());
        $this->assertSame(1, collect($komponen->get('tanyaJawab'))->where('pertanyaan', 'Kapan kelompok belajar dimulai?')->count());

        // Pertanyaan yang sama boleh dikirim lagi setelah jeda.
        $this->travel(3)->minutes();
        app(\App\Services\LayananKampanye::class)->kirimPertanyaan($siswa, $calon, 'Kapan kelompok belajar dimulai?');
        $this->assertSame(2, $calon->questions()->where('voter_id', $siswa->id)->count());
    }
}
