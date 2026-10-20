<?php

namespace Tests\Feature;

use App\Enums\JenisPemilih;
use App\Models\OsisPeriod;
use App\Models\Voter;
use App\Services\ImporPemilih;
use App\Services\LayananPeriode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PemilihPerPeriodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_pemilih_dibatasi_periode_aktif_dan_bisa_diimpor_ulang_tahun_berikutnya(): void
    {
        $lama = OsisPeriod::factory()->tahun(2025)->aktif()->create();
        $siswa = Voter::factory()->berkode('135790')->create(['nomor_induk' => '0091234567']);
        $this->assertSame($lama->id, $siswa->periode_id);

        app(LayananPeriode::class)->aktifkan(OsisPeriod::factory()->tahun(2026)->create());

        // Periode baru mulai tanpa pemilih; kode pilih periode lama tidak berlaku.
        $this->assertSame(0, Voter::count());
        $this->assertNull(Voter::where('kode_pilih', '135790')->first());
        $this->assertSame(1, Voter::diPeriode($lama)->count());

        // Siswa yang sama diimpor lagi untuk periode baru (NISN unik per periode).
        $csv = tempnam(sys_get_temp_dir(), 'pp').'.csv';
        file_put_contents($csv, "nisn,nama,kelas,tanggal_lahir\n0091234567,Siswa Naik Kelas,VIII-A,2013-05-21\n");
        $hasil = app(ImporPemilih::class)->impor($csv, JenisPemilih::Siswa);

        $this->assertSame(1, $hasil['tambah']);
        $this->assertSame('VIII-A', Voter::sole()->kelas);
        $this->assertSame(2, Voter::semuaPeriode()->where('nomor_induk', '0091234567')->count());
    }

    public function test_tanpa_periode_aktif_pemilih_tidak_difilter(): void
    {
        Voter::factory()->count(2)->create();

        $this->assertSame(2, Voter::count());
    }

    public function test_tabel_log_impor_dan_indeks_unik_per_periode(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumns('voter_imports', ['periode_id', 'user_id', 'jenis', 'nama_berkas', 'tambah', 'perbarui', 'sama', 'dilewati', 'galat', 'created_at']));

        $log = \App\Models\VoterImport::create(['jenis' => 'siswa', 'nama_berkas' => 'siswa.csv', 'tambah' => 3, 'dilewati' => 1, 'galat' => ['Baris 4: Nama belum diisi.']]);
        $this->assertSame(['Baris 4: Nama belum diisi.'], $log->fresh()->galat);

        $p = OsisPeriod::factory()->tahun(2026)->aktif()->create();
        Voter::factory()->create(['nomor_induk' => '0090000001', 'jenis' => 'siswa']);
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        Voter::factory()->create(['nomor_induk' => '0090000001', 'jenis' => 'siswa', 'periode_id' => $p->id]);
    }
}
