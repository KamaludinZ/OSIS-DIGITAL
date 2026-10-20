<?php

namespace Tests\Feature;

use App\Models\Ballot;
use App\Models\Candidate;
use Database\Seeders\PemilihanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SuaraAnonimTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabel_suara_anonim_tanpa_identitas_dan_waktu(): void
    {
        $kolom = Schema::getColumnListing('ballots');
        sort($kolom);

        $this->assertSame(['candidate_id', 'election_id', 'id'], $kolom);
        $this->assertNotContains('voter_id', $kolom);
        $this->assertNotContains('created_at', $kolom);
    }

    public function test_surat_suara_ber_id_acak_dan_tidak_bisa_diubah(): void
    {
        $this->seed(PemilihanSeeder::class);
        $calon = Candidate::first();

        $a = Ballot::create(['election_id' => $calon->election_id, 'candidate_id' => $calon->id]);
        $b = Ballot::create(['election_id' => $calon->election_id, 'candidate_id' => $calon->id]);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $a->id);
        $this->assertNotSame($a->id, $b->id);

        $this->expectException(\LogicException::class);
        $a->update(['candidate_id' => Candidate::latest('id')->value('id')]);
    }

    public function test_pilihan_tidak_bisa_dikaitkan_dengan_pemilih(): void
    {
        $this->seed(PemilihanSeeder::class);
        $calon = Candidate::where('nomor_urut', 2)->first();
        $pemilih = \App\Models\Voter::factory()->create();

        $suara = app(\App\Services\LayananPemilihan::class)->kirimSuara($pemilih, $calon->election, $calon->id);

        // Catatan kehadiran tidak memuat pilihan sama sekali.
        $this->assertFalse(Schema::hasColumn('votes', 'candidate_id'));
        $this->assertArrayNotHasKey('candidate_id', \Illuminate\Support\Facades\DB::table('votes')->where('id', $suara->id)->first() ? (array) \Illuminate\Support\Facades\DB::table('votes')->where('id', $suara->id)->first() : []);
        // Pilihan tercatat anonim dan terhitung di rekap.
        $this->assertSame(1, Ballot::where('candidate_id', $calon->id)->count());
        $this->assertSame(1, collect(app(\App\Services\LayananHasil::class)->perolehanSuara($calon->election))->firstWhere('id', $calon->id)['suara']);
    }

    public function test_pilihan_lama_dipindah_ke_surat_suara_anonim(): void
    {
        $this->seed(PemilihanSeeder::class);
        $calon = Candidate::where('nomor_urut', 1)->first();
        $migrasi = require database_path('migrations/2026_10_20_001000_pisahkan_pilihan_dari_kehadiran_pemilih.php');
        $migrasi->down();

        $pemilih = \App\Models\Voter::factory()->count(2)->create();
        foreach ($pemilih as $i => $p) {
            \Illuminate\Support\Facades\DB::table('votes')->insert(['election_id' => $calon->election_id, 'voter_id' => $p->id, 'candidate_id' => $calon->id, 'kode_bukti' => 'OSIM-LAMA-000'.$i, 'created_at' => now()]);
        }

        $migrasi->up();

        $this->assertSame(2, Ballot::where('candidate_id', $calon->id)->count());
        $this->assertSame(2, \Illuminate\Support\Facades\DB::table('votes')->count());
        $this->assertFalse(Schema::hasColumn('votes', 'candidate_id'));
    }
}
