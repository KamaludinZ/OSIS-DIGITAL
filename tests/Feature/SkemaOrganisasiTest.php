<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Member;
use Database\Seeders\OrganisasiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkemaOrganisasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_organisasi_mengisi_inti_dan_divisi_dan_bisa_diulang(): void
    {
        $this->seed([OrganisasiSeeder::class, OrganisasiSeeder::class]);

        $this->assertSame(26, Member::periode()->count());
        $this->assertSame(4, Member::periode()->inti()->count());
        $this->assertSame(4, Division::where('nama', 'Divisi Keagamaan')->first()->members()->count());
    }

    public function test_periode_aktif_mengikuti_tahun_ajaran(): void
    {
        $this->travelTo('2026-10-12');
        $this->assertSame('2026/2027', Member::periodeAktif());

        $this->travelTo('2027-03-01');
        $this->assertSame('2026/2027', Member::periodeAktif());

        $this->travelTo('2027-07-15');
        $this->assertSame('2027/2028', Member::periodeAktif());
    }

    public function test_hapus_divisi_tidak_menghapus_anggota(): void
    {
        $d = Division::create(['nama' => 'Divisi Uji']);
        $m = Member::factory()->create(['divisi_id' => $d->id]);

        $d->delete();

        $this->assertNull($m->fresh()->divisi_id);
    }

    public function test_program_kerja_per_divisi_per_periode(): void
    {
        $divisi = Division::create(['nama' => 'Divisi Lingkungan']);
        $lama = \App\Models\OsisPeriod::factory()->tahun(2025)->aktif()->create();
        \App\Models\WorkProgram::factory()->create(['nama' => 'Bank Sampah', 'divisi_id' => $divisi->id]);

        app(\App\Services\LayananPeriode::class)->aktifkan(\App\Models\OsisPeriod::factory()->tahun(2026)->create());
        \App\Models\WorkProgram::factory()->create(['nama' => 'Kebun Sekolah', 'divisi_id' => $divisi->id]);

        $this->assertSame(['Kebun Sekolah'], $divisi->workPrograms()->pluck('nama')->all());
        $this->assertSame(['Bank Sampah'], \App\Models\WorkProgram::diPeriode($lama)->pluck('nama')->all());
    }

    public function test_anggota_pengurus_mengikuti_periode(): void
    {
        $aktif = \App\Models\OsisPeriod::factory()->tahun(2026)->aktif()->create();
        $m = Member::factory()->create(['periode' => '2026/2027']);
        $this->assertSame($aktif->id, $m->periode_id);

        $tanpaTeks = Member::create(['nama' => 'Anggota Baru', 'jabatan' => 'Anggota']);
        $this->assertSame('2026/2027', $tanpaTeks->periode);
        $this->assertSame($aktif->id, $tanpaTeks->periode_id);
        $this->assertSame(2, Member::periode('2026/2027')->count());
    }
}
