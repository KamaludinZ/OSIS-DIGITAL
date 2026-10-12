<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Enums\StatusPemilihan;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkemaPemilihanTest extends TestCase
{
    use RefreshDatabase;

    public function test_relasi_pemilihan_kandidat_dan_suara(): void
    {
        $pemilihan = Election::factory()->create();
        $calon = Candidate::factory()->for($pemilihan)->create(['nomor_urut' => 1]);
        $siswa = User::factory()->create();

        Vote::create(['election_id' => $pemilihan->id, 'user_id' => $siswa->id, 'candidate_id' => $calon->id, 'kode_bukti' => 'OSIM-AAAA-BBBB']);

        $this->assertSame(StatusPemilihan::Berjalan, $pemilihan->status);
        $this->assertSame(Peran::Siswa, $siswa->role);
        $this->assertIsArray($calon->fresh()->misi);
        $this->assertSame(1, $pemilihan->votes()->count());
        $this->assertTrue($siswa->sudahMemilih($pemilihan));
        $this->assertTrue($pemilihan->sedangDibuka());
    }

    public function test_satu_siswa_hanya_bisa_memilih_sekali_per_pemilihan(): void
    {
        $pemilihan = Election::factory()->create();
        [$a, $b] = Candidate::factory()->for($pemilihan)->count(2)->create();
        $siswa = User::factory()->create();

        Vote::create(['election_id' => $pemilihan->id, 'user_id' => $siswa->id, 'candidate_id' => $a->id, 'kode_bukti' => 'OSIM-1111-1111']);

        $this->expectException(QueryException::class);
        Vote::create(['election_id' => $pemilihan->id, 'user_id' => $siswa->id, 'candidate_id' => $b->id, 'kode_bukti' => 'OSIM-2222-2222']);
    }

    public function test_nomor_urut_unik_per_pemilihan(): void
    {
        $pemilihan = Election::factory()->create();
        Candidate::factory()->for($pemilihan)->create(['nomor_urut' => 1]);

        $this->expectException(QueryException::class);
        Candidate::factory()->for($pemilihan)->create(['nomor_urut' => 1]);
    }

    public function test_pemilihan_aktif_mengutamakan_yang_berjalan(): void
    {
        Election::factory()->selesai()->create(['judul' => 'Lama']);
        Election::factory()->draft()->create(['judul' => 'Nanti']);
        Election::factory()->create(['judul' => 'Sekarang']);

        $this->assertSame('Sekarang', Election::aktif()->judul);
    }

    public function test_layanan_menghitung_status_efektif_dan_daftar_kandidat(): void
    {
        $layanan = app(\App\Services\LayananPemilihan::class);
        $pemilihan = Election::factory()->create(['mulai' => now()->addHour(), 'selesai' => now()->addHours(3)]);
        Candidate::factory()->for($pemilihan)->create(['nomor_urut' => 2]);
        Candidate::factory()->for($pemilihan)->create(['nomor_urut' => 1]);

        $this->assertSame('draft', $layanan->ringkasanPemilihan()['status']);
        $this->assertSame([1, 2], array_column($layanan->daftarKandidat(), 'nomor_urut'));

        $this->travel(2)->hours();
        $this->assertSame('berjalan', $layanan->ringkasanPemilihan()['status']);

        $this->travel(2)->hours();
        $this->assertSame('selesai', $layanan->ringkasanPemilihan()['status']);
    }
}
