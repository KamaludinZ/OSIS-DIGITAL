<?php

namespace Tests\Feature;

use App\Models\Election;
use App\Models\Member;
use App\Models\OsisPeriod;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrasiPeriodeIdTest extends TestCase
{
    use RefreshDatabase;

    private const TABEL = ['elections', 'candidates', 'members', 'work_programs', 'events', 'social_posts'];

    public function test_tabel_operasional_punya_kolom_periode_id(): void
    {
        foreach (self::TABEL as $tabel) {
            $this->assertTrue(Schema::hasColumn($tabel, 'periode_id'), $tabel);
        }
    }

    public function test_data_lama_diisi_ke_periode_yang_sesuai(): void
    {
        $this->travelTo('2026-10-20');
        $migrasi = require database_path('migrations/2026_10_20_000200_add_periode_id_to_tabel_operasional.php');
        $migrasi->down();

        Member::factory()->create(['periode' => '2025/2026']);
        Member::factory()->create(['periode' => '2026/2027']);
        $pemilihan = Election::factory()->create();

        $migrasi->up();
        OsisPeriod::lupakanAktif(); // migrasi menulis langsung ke basis data, memo periode aktif dibuang

        $this->assertSame(['2025/2026' => 'arsip', '2026/2027' => 'aktif'], OsisPeriod::orderBy('nama')->get()->mapWithKeys(fn ($p) => [$p->nama => $p->status->value])->all());
        $aktif = OsisPeriod::aktif();
        $this->assertSame($aktif->id, DB::table('elections')->where('id', $pemilihan->id)->value('periode_id'));
        $this->assertSame($aktif->id, DB::table('members')->where('periode', '2026/2027')->value('periode_id'));
        $this->assertNotSame($aktif->id, DB::table('members')->where('periode', '2025/2026')->value('periode_id'));
    }

    public function test_periode_yang_masih_punya_data_tidak_bisa_dihapus(): void
    {
        $p = OsisPeriod::factory()->create();
        DB::table('members')->insert(['nama' => 'Uji', 'jabatan' => 'Anggota', 'periode' => $p->nama, 'periode_id' => $p->id]);

        $this->expectException(QueryException::class);
        DB::table('osis_periods')->where('id', $p->id)->delete();
    }
}
