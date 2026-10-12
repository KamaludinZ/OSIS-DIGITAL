<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\User;
use App\Models\Vote;
use App\Enums\Peran;
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
        $this->actingAs(User::factory()->create());
    }

    /** ID kandidat berdasarkan nomor urut pada data contoh. */
    private function idNomor(int $nomor): int
    {
        return Candidate::where('nomor_urut', $nomor)->value('id');
    }

    public function test_bilik_suara_menampilkan_daftar_kandidat_berurutan(): void
    {
        $this->withSession(['panduan_dilewati' => true])->get('/bilik-suara')
            ->assertOk()
            ->assertSeeInOrder(['Pasangan Calon 1', 'Pasangan Calon 2', 'Pasangan Calon 3'])
            ->assertSee('Foto belum tersedia');
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

    public function test_hanya_satu_kandidat_yang_aktif_dipilih(): void
    {
        Volt::test('bilik-suara')->call('tutupPanduan')
            ->assertSet('dipilih', null)
            ->call('pilih', $this->idNomor(2))
            ->assertSet('dipilih', $this->idNomor(2))
            ->assertSee('Nomor 2 — Nadia Kusuma')
            ->call('pilih', $this->idNomor(3))
            ->assertSet('dipilih', $this->idNomor(3))
            ->call('pilih', $this->idNomor(3))
            ->assertSet('dipilih', null)
            ->call('pilih', 99)
            ->assertSet('dipilih', null);
    }

    public function test_konfirmasi_sebelum_kirim_suara(): void
    {
        Volt::test('bilik-suara')->call('tutupPanduan')
            ->call('bukaKonfirmasi')
            ->assertSet('konfirmasi', false)
            ->call('pilih', $this->idNomor(1))
            ->call('kirimSuara')
            ->assertSet('terkirim', false)
            ->call('bukaKonfirmasi')
            ->assertSet('konfirmasi', true)
            ->assertSee('Apakah Anda yakin memilih')
            ->call('kirimSuara')
            ->assertSet('konfirmasi', false)
            ->assertSet('terkirim', true)
            ->assertRedirect(route('bilik-suara.bukti'));
    }

    public function test_pilihan_kosong_menampilkan_pesan_dan_kirim_tidak_ganda(): void
    {
        Volt::test('bilik-suara')->call('tutupPanduan')
            ->call('bukaKonfirmasi')
            ->assertHasErrors('dipilih')
            ->assertSee('Pilih salah satu pasangan calon terlebih dahulu.')
            ->call('pilih', $this->idNomor(2))
            ->assertHasNoErrors()
            ->call('bukaKonfirmasi')
            ->call('kirimSuara')
            ->assertSet('terkirim', true)
            ->call('bukaKonfirmasi')
            ->assertSet('konfirmasi', false);
    }

    public function test_halaman_bukti_suara_dari_database_tidak_membuka_pilihan(): void
    {
        Vote::create(['election_id' => Election::value('id'), 'user_id' => auth()->id(), 'candidate_id' => $this->idNomor(2), 'kode_bukti' => 'OSIM-ABCD-EFGH']);

        $this->get('/bilik-suara/bukti')
            ->assertOk()
            ->assertSee('Suara Anda sudah tercatat')
            ->assertSee('OSIM-ABCD-EFGH')
            ->assertSee('Stempel: SUDAH MEMILIH')
            ->assertDontSee('Nadia Kusuma');
    }

    public function test_bukti_hanya_untuk_yang_sudah_memilih(): void
    {
        $this->get('/bilik-suara/bukti')->assertRedirect(route('bilik-suara'));

        auth()->logout();
        $this->get('/bilik-suara/bukti')->assertRedirect(route('login'));
    }

    public function test_tombol_beranda_berubah_setelah_memilih(): void
    {
        $this->get('/')->assertOk()->assertSee('Mulai Memilih')->assertDontSee('Anda sudah memilih');

        Volt::test('bilik-suara')->call('tutupPanduan')->call('pilih', $this->idNomor(1))->call('bukaKonfirmasi')->call('kirimSuara');

        $this->get('/')->assertOk()->assertSee('Anda sudah memilih')->assertDontSee('Mulai Memilih');
    }

    public function test_panduan_tampil_pertama_kali_dan_bisa_dilewati(): void
    {
        $this->get('/bilik-suara')->assertOk()->assertSee('Cara memilih')->assertSee('Lewati');

        Volt::test('bilik-suara')
            ->assertSet('panduan', true)
            ->call('tutupPanduan')
            ->assertSet('panduan', false)
            ->assertSee('Pilih nomor 1')
            ->call('bukaPanduan')
            ->assertSet('panduan', true);

        $this->get('/bilik-suara')->assertOk()->assertDontSee('Mengerti, mulai memilih');
    }

    public function test_tamu_diminta_masuk_dan_tidak_bisa_memilih(): void
    {
        auth()->logout();

        $this->withSession(['panduan_dilewati' => true])->get('/bilik-suara')
            ->assertOk()
            ->assertSee('Masuk dengan akun sekolah')
            ->assertDontSee('Pilih nomor 1');

        Volt::test('bilik-suara')->call('pilih', $this->idNomor(1))->assertSet('dipilih', null);
    }

    public function test_akun_bukan_siswa_tidak_layak_memilih(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));

        Volt::test('bilik-suara')
            ->call('tutupPanduan')
            ->assertSee('Hanya siswa terdaftar yang dapat memilih')
            ->call('pilih', $this->idNomor(1))
            ->assertSet('dipilih', null);
    }

    public function test_siswa_yang_sudah_memilih_tidak_layak_lagi(): void
    {
        $siswa = User::factory()->create();
        Vote::create(['election_id' => Election::value('id'), 'user_id' => $siswa->id, 'candidate_id' => $this->idNomor(2), 'kode_bukti' => 'OSIM-TEST-0001']);

        $this->actingAs($siswa);

        Volt::test('bilik-suara')
            ->call('tutupPanduan')
            ->assertSee('Anda sudah menggunakan hak pilih')
            ->assertSee('Lihat bukti')
            ->call('pilih', $this->idNomor(1))
            ->assertSet('dipilih', null);

        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($siswa)->allows('memilih', Election::first()));
    }

    public function test_kelayakan_dicek_ulang_saat_kirim_suara(): void
    {
        $komponen = Volt::test('bilik-suara')
            ->call('tutupPanduan')
            ->call('pilih', $this->idNomor(1))
            ->call('bukaKonfirmasi')
            ->assertSet('konfirmasi', true);

        Election::query()->update(['status' => 'selesai']);

        $komponen->call('kirimSuara')
            ->assertSet('terkirim', false)
            ->assertSet('konfirmasi', false)
            ->assertHasErrors('dipilih');
    }

    public function test_kirim_suara_tersimpan_di_database_tanpa_bisa_diulang(): void
    {
        $siswa = auth()->user();

        Volt::test('bilik-suara')
            ->call('tutupPanduan')
            ->call('pilih', $this->idNomor(2))
            ->call('bukaKonfirmasi')
            ->call('kirimSuara')
            ->assertHasNoErrors()
            ->assertRedirect(route('bilik-suara.bukti'));

        $this->assertDatabaseHas('votes', ['user_id' => $siswa->id, 'candidate_id' => $this->idNomor(2)]);
        $this->assertMatchesRegularExpression('/^OSIM-[A-Z0-9]{4}-[A-Z0-9]{4}$/', Vote::value('kode_bukti'));

        // Tab lain yang masih terbuka mencoba mengirim lagi.
        Volt::test('bilik-suara')
            ->call('tutupPanduan')
            ->call('pilih', $this->idNomor(1))
            ->assertSet('dipilih', null);

        $this->assertSame(1, Vote::count());
    }

    public function test_layanan_menolak_suara_ganda_dan_kandidat_asing(): void
    {
        $layanan = app(\App\Services\LayananPemilihan::class);
        $pemilihan = Election::first();
        $siswa = User::factory()->create();
        $asing = Candidate::factory()->create();

        try {
            $layanan->kirimSuara($siswa, $pemilihan, $asing->id);
            $this->fail('Kandidat dari pemilihan lain seharusnya ditolak.');
        } catch (\App\Exceptions\SuaraDitolak $e) {
            $this->assertSame('kandidat_tidak_valid', $e->alasan);
        }

        $layanan->kirimSuara($siswa, $pemilihan, $this->idNomor(1));

        $this->expectException(\App\Exceptions\SuaraDitolak::class);
        $layanan->kirimSuara($siswa, $pemilihan, $this->idNomor(2));
    }
}
