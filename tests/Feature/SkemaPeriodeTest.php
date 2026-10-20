<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SkemaPeriodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabel_osis_periods_punya_kolom_periode(): void
    {
        $this->assertTrue(Schema::hasColumns('osis_periods', ['id', 'nama', 'mulai', 'selesai', 'status', 'created_at', 'updated_at']));
    }

    public function test_basis_data_menolak_dua_periode_aktif(): void
    {
        $baris = fn (string $nama, string $status) => ['nama' => $nama, 'mulai' => '2026-07-13', 'selesai' => '2027-06-25', 'status' => $status];

        DB::table('osis_periods')->insert([$baris('2024/2025', 'arsip'), $baris('2025/2026', 'arsip'), $baris('2026/2027', 'aktif'), $baris('2027/2028', 'disiapkan'), $baris('2028/2029', 'disiapkan')]);

        $this->expectException(QueryException::class);
        DB::table('osis_periods')->where('nama', '2027/2028')->update(['status' => 'aktif']);
    }

    public function test_nama_periode_unik(): void
    {
        DB::table('osis_periods')->insert(['nama' => '2026/2027', 'mulai' => '2026-07-13', 'selesai' => '2027-06-25']);

        $this->expectException(QueryException::class);
        DB::table('osis_periods')->insert(['nama' => '2026/2027', 'mulai' => '2026-07-13', 'selesai' => '2027-06-25']);
    }

    public function test_kolom_is_aktif_menandai_pengurus_menjabat(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'is_aktif'));

        $menjabat = \App\Models\User::factory()->pengurusMenjabat()->create();
        $mantan = \App\Models\User::factory()->create(['role' => \App\Enums\Peran::Pengurus]);
        $nonaktif = \App\Models\User::factory()->pengurusMenjabat()->create(['aktif' => false]);

        $this->assertFalse($mantan->fresh()->is_aktif);
        $this->assertTrue($menjabat->menjabat());
        $this->assertFalse($mantan->menjabat());
        $this->assertFalse($nonaktif->menjabat());
        $this->assertSame([$menjabat->id], \App\Models\User::pengurusMenjabat()->pluck('id')->all());
    }

    public function test_kolom_mode_pemilihan_per_periode(): void
    {
        $this->assertTrue(Schema::hasColumns('osis_periods', ['mode_pemilihan', 'mode_diubah_pada', 'mode_diubah_oleh']));

        $pengubah = \App\Models\User::factory()->create(['role' => \App\Enums\Peran::Panitia]);
        $periode = \App\Models\OsisPeriod::factory()->aktif()->create();
        $this->assertFalse($periode->fresh()->mode_pemilihan);

        $periode->update(['mode_pemilihan' => true, 'mode_diubah_pada' => now(), 'mode_diubah_oleh' => $pengubah->id]);
        $this->assertTrue($periode->fresh()->mode_pemilihan);
        $this->assertTrue($periode->fresh()->pengubahMode->is($pengubah));

        // Akun dihapus: jejak pengubah dikosongkan, mode tetap.
        $pengubah->delete();
        $this->assertNull($periode->fresh()->mode_diubah_oleh);
        $this->assertTrue($periode->fresh()->mode_pemilihan);
    }

    public function test_periode_lama_tetap_dalam_mode_pemilihan_setelah_migrasi(): void
    {
        $migrasi = require database_path('migrations/2026_10_20_000400_add_mode_pemilihan_to_osis_periods_table.php');
        $migrasi->down();
        DB::table('osis_periods')->insert(['nama' => '2026/2027', 'mulai' => '2026-07-13', 'selesai' => '2027-06-25', 'status' => 'aktif']);

        $migrasi->up();

        $this->assertTrue((bool) DB::table('osis_periods')->value('mode_pemilihan'));
    }

    public function test_tabel_organization_structures_satu_bagan_per_periode(): void
    {
        $this->assertTrue(Schema::hasColumns('organization_structures', ['id', 'periode_id', 'gambar', 'rasio', 'diunggah_oleh', 'created_at', 'updated_at']));

        $periode = \App\Models\OsisPeriod::factory()->create();
        DB::table('organization_structures')->insert(['periode_id' => $periode->id, 'gambar' => 'struktur/a.png']);
        $this->assertSame('4:3', DB::table('organization_structures')->value('rasio'));

        $this->expectException(QueryException::class);
        DB::table('organization_structures')->insert(['periode_id' => $periode->id, 'gambar' => 'struktur/b.png']);
    }

    public function test_tabel_suggestions(): void
    {
        $this->assertTrue(Schema::hasColumns('suggestions', ['id', 'periode_id', 'isi', 'nama', 'kelas', 'tanggapan', 'ditanggapi_oleh', 'ditanggapi_pada', 'created_at', 'updated_at']));

        $periode = \App\Models\OsisPeriod::factory()->create();
        DB::table('suggestions')->insert(['periode_id' => $periode->id, 'isi' => 'Saran anonim']);
        $this->assertNull(DB::table('suggestions')->value('nama'));

        // Periode yang masih menyimpan saran tidak bisa dihapus.
        $this->expectException(QueryException::class);
        DB::table('osis_periods')->where('id', $periode->id)->delete();
    }

    public function test_tabel_social_links_satu_akun_per_platform_per_periode(): void
    {
        $this->assertTrue(Schema::hasColumns('social_links', ['id', 'periode_id', 'platform', 'nama_akun', 'tautan', 'urutan', 'jumlah_klik']));

        [$a, $b] = \App\Models\OsisPeriod::factory()->count(2)->create();
        $baris = fn ($p) => ['periode_id' => $p->id, 'platform' => 'instagram', 'nama_akun' => '@osim', 'tautan' => 'https://www.instagram.com/osim'];
        DB::table('social_links')->insert([$baris($a), $baris($b)]);
        $this->assertSame(2, DB::table('social_links')->count());

        $this->expectException(QueryException::class);
        DB::table('social_links')->insert($baris($a));
    }
}
