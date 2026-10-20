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

    public function test_tabel_rekap_agregat_terisi_dari_surat_suara_lama(): void
    {
        $this->assertSame(['candidate_id', 'election_id', 'jumlah'], collect(\Illuminate\Support\Facades\Schema::getColumnListing('vote_tallies'))->sort()->values()->all());

        $this->seed(\Database\Seeders\PemilihanSeeder::class);
        $calon = \App\Models\Candidate::first();
        \App\Models\Ballot::create(['election_id' => $calon->election_id, 'candidate_id' => $calon->id]);
        \App\Models\Ballot::create(['election_id' => $calon->election_id, 'candidate_id' => $calon->id]);
        \Illuminate\Support\Facades\DB::table('vote_tallies')->delete();

        $migrasi = require database_path('migrations/2026_10_20_001200_create_vote_tallies_table.php');
        $migrasi->down();
        $migrasi->up();

        $this->assertSame(2, \App\Models\VoteTally::where('candidate_id', $calon->id)->value('jumlah'));
    }

    public function test_rekap_agregat_bertambah_bersama_suara_dan_bisa_dihitung_ulang(): void
    {
        $e = Election::factory()->create();
        $a = Candidate::factory()->for($e)->create(['nomor_urut' => 1]);
        $b = Candidate::factory()->for($e)->create(['nomor_urut' => 2]);
        $this->suara($e, $a, 2);
        app(\App\Services\LayananPemilihan::class)->kirimSuara(\App\Models\Voter::factory()->create(), $e, $b->id);

        $this->assertSame(2, \App\Models\VoteTally::where('candidate_id', $a->id)->value('jumlah'));
        $this->assertSame(1, \App\Models\VoteTally::where('candidate_id', $b->id)->value('jumlah'));

        // Rekap dibaca dari tabel agregat tanpa GROUP BY atas baris suara.
        DB::enableQueryLog();
        app(LayananHasil::class)->perolehanSuara($e);
        $this->assertEmpty(collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'from "ballots"')));

        // Rekap rusak (mis. diubah manual) dipulihkan dari surat suara.
        \App\Models\VoteTally::where('candidate_id', $a->id)->update(['jumlah' => 99]);
        $this->artisan('osim:hitung-ulang-rekap', ['pemilihan' => $e->id])->expectsOutputToContain('1 kandidat diperbaiki')->assertSuccessful();
        $this->assertSame([2, 1], array_column(app(LayananHasil::class)->perolehanSuara($e), 'suara'));
        $this->artisan('osim:hitung-ulang-rekap', ['pemilihan' => $e->id])->expectsOutputToContain('sudah sesuai')->assertSuccessful();
    }

    public function test_rekap_di_cache_dan_kedaluwarsa_saat_ada_suara_atau_kandidat_berubah(): void
    {
        config(['cache.default' => 'array']);
        $e = Election::factory()->create();
        $a = Candidate::factory()->for($e)->create(['nomor_urut' => 1, 'nama_ketua' => 'Nama Lama']);
        $layanan = app(LayananHasil::class);

        $this->assertSame(0, $layanan->rekap($e)['suara_masuk']);

        // Tanpa invalidasi, rekap tetap dari cache (tidak ada kueri lagi).
        DB::enableQueryLog();
        $layanan->rekap($e);
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();

        \App\Models\Ballot::create(['election_id' => $e->id, 'candidate_id' => $a->id]);
        $this->assertSame(1, $layanan->rekap($e)['suara_masuk']);

        $a->update(['nama_ketua' => 'Nama Baru']);
        $this->assertSame('Nama Baru', $layanan->rekap($e)['kandidat'][0]['nama_ketua']);
    }
}
