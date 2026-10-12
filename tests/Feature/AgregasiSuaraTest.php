<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\User;
use App\Models\Vote;
use App\Services\LayananHasil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgregasiSuaraTest extends TestCase
{
    use RefreshDatabase;

    private function suara(Election $e, Candidate $c, int $jumlah): void
    {
        \App\Models\Voter::factory()->count($jumlah)->create()->each(fn ($u) => Vote::create([
            'election_id' => $e->id, 'voter_id' => $u->id, 'candidate_id' => $c->id, 'kode_bukti' => 'OSIM-'.Str::upper(Str::random(9)),
        ]));
    }

    public function test_perolehan_dihitung_per_kandidat_termasuk_nol(): void
    {
        $e = Election::factory()->create();
        [$a, $b, $c] = [
            Candidate::factory()->for($e)->create(['nomor_urut' => 1]),
            Candidate::factory()->for($e)->create(['nomor_urut' => 2]),
            Candidate::factory()->for($e)->create(['nomor_urut' => 3]),
        ];
        $this->suara($e, $a, 3);
        $this->suara($e, $b, 5);

        // Suara dari periode lain tidak ikut terhitung.
        $lain = Election::factory()->selesai()->create();
        $this->suara($lain, Candidate::factory()->for($lain)->create(['nomor_urut' => 1]), 4);

        $hasil = app(LayananHasil::class)->perolehanSuara($e);

        $this->assertSame([1, 2, 3], array_column($hasil, 'nomor_urut'));
        $this->assertSame([3, 5, 0], array_column($hasil, 'suara'));
    }

    public function test_rekap_memakai_query_agregasi_ringan(): void
    {
        $e = Election::factory()->create(['hasil_publik' => true]);
        $c = Candidate::factory()->for($e)->create(['nomor_urut' => 1]);
        $this->suara($e, $c, 6);
        \App\Models\Voter::factory()->count(4)->create();
        \App\Models\Voter::factory()->create(['verifikasi' => 'ditolak']);
        User::factory()->create(['role' => Peran::Panitia]);

        DB::enableQueryLog();
        $rekap = app(LayananHasil::class)->rekap($e);
        $jumlahQuery = count(DB::getQueryLog());

        $this->assertSame(6, $rekap['suara_masuk']);
        $this->assertSame(10, $rekap['total_pemilih']); // 6 pemilih + 4 belum memilih; ditolak & akun panitia tidak dihitung
        $this->assertTrue($rekap['hasil_publik']);
        $this->assertSame('berjalan', $rekap['status']);
        $this->assertLessThanOrEqual(3, $jumlahQuery);
    }

    public function test_rekap_kosong_tanpa_pemilihan(): void
    {
        $this->assertNull(app(LayananHasil::class)->rekap());
    }
}
