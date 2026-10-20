<?php

namespace Tests\Feature;

use App\Models\OrganizationStructure;
use App\Models\OsisPeriod;
use App\Models\User;
use App\Services\LayananStruktur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LayananStrukturTest extends TestCase
{
    use RefreshDatabase;

    public function test_unggah_lalu_ganti_menghapus_berkas_lama(): void
    {
        Storage::fake('public');
        OsisPeriod::factory()->tahun(2026)->aktif()->create();
        $layanan = app(LayananStruktur::class);
        $oleh = User::factory()->pengurusMenjabat()->create();

        Storage::disk('public')->put('struktur/a.png', 'a');
        $pertama = $layanan->unggah('struktur/a.png', '4:3', $oleh);
        $this->assertTrue($pertama->pengunggah->is($oleh));

        Storage::disk('public')->put('struktur/b.png', 'b');
        $kedua = $layanan->unggah('struktur/b.png', '3:4', $oleh);

        $this->assertSame($pertama->id, $kedua->id);
        $this->assertSame(1, OrganizationStructure::count());
        $this->assertSame('3:4', $layanan->aktif()->rasio);
        Storage::disk('public')->assertMissing('struktur/a.png');
        Storage::disk('public')->assertExists('struktur/b.png');
    }

    public function test_ubah_rasio_dan_hapus(): void
    {
        Storage::fake('public');
        OsisPeriod::factory()->tahun(2026)->aktif()->create();
        $layanan = app(LayananStruktur::class);
        Storage::disk('public')->put('struktur/a.png', 'a');
        $layanan->unggah('struktur/a.png', '4:3', User::factory()->create());

        $this->assertSame('3:4', $layanan->ubahRasio('3:4')->rasio);

        $layanan->hapus();
        $this->assertNull($layanan->aktif());
        Storage::disk('public')->assertMissing('struktur/a.png');
    }

    public function test_ditolak_tanpa_periode_aktif_atau_rasio_salah(): void
    {
        $layanan = app(LayananStruktur::class);

        try {
            $layanan->unggah('struktur/a.png', '4:3', User::factory()->create());
            $this->fail('Seharusnya ditolak tanpa periode aktif.');
        } catch (\App\Exceptions\PeriodeDitolak) {
        }

        OsisPeriod::factory()->tahun(2026)->aktif()->create();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $layanan->unggah('struktur/a.png', '16:9', User::factory()->create());
    }
}
