<?php

namespace Tests\Feature;

use App\Models\OrganizationStructure;
use App\Models\OsisPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelStrukturTest extends TestCase
{
    use RefreshDatabase;

    public function test_bagan_mengikuti_periode_aktif(): void
    {
        $lama = OsisPeriod::factory()->tahun(2025)->aktif()->create();
        $baganLama = OrganizationStructure::create(['gambar' => 'struktur/lama.png', 'rasio' => '3:4']);
        $this->assertSame($lama->id, $baganLama->periode_id);
        $this->assertTrue(OrganizationStructure::aktif()->is($baganLama));

        app(\App\Services\LayananPeriode::class)->aktifkan($baru = OsisPeriod::factory()->tahun(2026)->create());

        // Periode baru mulai kosong; bagan lama tetap di arsip.
        $this->assertNull(OrganizationStructure::aktif());
        $this->assertTrue($lama->fresh()->struktur->is($baganLama));
        $this->assertTrue($baganLama->fresh()->periode->is($lama));
        $this->assertTrue($baganLama->tinggi());

        $baganBaru = OrganizationStructure::create(['gambar' => 'struktur/baru.png']);
        $this->assertSame($baru->id, $baganBaru->periode_id);
        $this->assertSame('4:3', $baganBaru->rasio);
        $this->assertStringEndsWith('/storage/struktur/baru.png', $baganBaru->urlGambar());
        $this->assertTrue($baru->fresh()->punyaData());
    }

    public function test_bagan_periode_arsip_tidak_bisa_diubah(): void
    {
        $lama = OsisPeriod::factory()->tahun(2025)->aktif()->create();
        OrganizationStructure::create(['gambar' => 'struktur/lama.png']);
        app(\App\Services\LayananPeriode::class)->aktifkan(OsisPeriod::factory()->tahun(2026)->create());

        $this->expectException(\App\Exceptions\PeriodeDitolak::class);
        $lama->struktur->update(['rasio' => '3:4']);
    }
}
