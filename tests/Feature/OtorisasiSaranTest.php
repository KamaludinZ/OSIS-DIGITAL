<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\OsisPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtorisasiSaranTest extends TestCase
{
    use RefreshDatabase;

    private function saran(OsisPeriod $periode): Model
    {
        return (new class extends Model {})->forceFill(['periode_id' => $periode->id]);
    }

    public function test_hanya_pengurus_menjabat_yang_boleh_menanggapi_saran(): void
    {
        $aktif = OsisPeriod::factory()->tahun(2026)->aktif()->create();
        $saran = $this->saran($aktif);

        $this->assertTrue(User::factory()->pengurusMenjabat()->create()->can('tanggapi-saran', $saran));

        $tidakBoleh = [
            'pengurus tidak menjabat' => User::factory()->create(['role' => Peran::Pengurus]),
            'akun pengurus nonaktif' => User::factory()->pengurusMenjabat()->create(['aktif' => false]),
            'panitia' => User::factory()->create(['role' => Peran::Panitia, 'is_aktif' => true]),
            'admin' => User::factory()->create(['role' => Peran::Admin]),
            'kandidat' => User::factory()->create(['role' => Peran::Kandidat]),
        ];

        foreach ($tidakBoleh as $siapa => $pengguna) {
            $this->assertFalse($pengguna->can('tanggapi-saran', $saran), $siapa);
        }
    }

    public function test_saran_periode_arsip_tidak_bisa_ditanggapi(): void
    {
        $lama = OsisPeriod::factory()->tahun(2025)->arsip()->create();
        $aktif = OsisPeriod::factory()->tahun(2026)->aktif()->create();
        $pengurus = User::factory()->pengurusMenjabat()->create();

        $this->assertFalse($pengurus->can('tanggapi-saran', $this->saran($lama)));
        $this->assertTrue($pengurus->can('tanggapi-saran', $this->saran($aktif)));
        $this->assertTrue($pengurus->can('tanggapi-saran'));

        $aktif->update(['status' => 'arsip']);
        $this->assertFalse($pengurus->can('tanggapi-saran'));
    }
}
