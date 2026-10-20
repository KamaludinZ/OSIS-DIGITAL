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

        DB::table('osis_periods')->insert([$baris('2025/2026', 'arsip'), $baris('2026/2027', 'aktif'), $baris('2027/2028', 'disiapkan')]);

        $this->expectException(QueryException::class);
        DB::table('osis_periods')->where('nama', '2027/2028')->update(['status' => 'aktif']);
    }

    public function test_nama_periode_unik(): void
    {
        DB::table('osis_periods')->insert(['nama' => '2026/2027', 'mulai' => '2026-07-13', 'selesai' => '2027-06-25']);

        $this->expectException(QueryException::class);
        DB::table('osis_periods')->insert(['nama' => '2026/2027', 'mulai' => '2026-07-13', 'selesai' => '2027-06-25']);
    }
}
