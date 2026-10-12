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
}
