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
}
