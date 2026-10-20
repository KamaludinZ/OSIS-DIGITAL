<?php

namespace Tests\Feature;

use App\Enums\PlatformSosmed;
use App\Models\OsisPeriod;
use App\Models\SocialLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelSocialLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_akun_mengikuti_periode_aktif_dan_arsip_tetap_tersimpan(): void
    {
        $lama = OsisPeriod::factory()->tahun(2025)->aktif()->create();
        $akunLama = SocialLink::factory()->create();
        $this->assertSame($lama->id, $akunLama->periode_id);
        $this->assertSame(PlatformSosmed::Instagram, $akunLama->platform);
        $this->assertTrue($akunLama->tautanSah());

        app(\App\Services\LayananPeriode::class)->aktifkan($baru = OsisPeriod::factory()->tahun(2026)->create());
        $this->assertSame(0, SocialLink::count());

        $akunBaru = SocialLink::factory()->platform(PlatformSosmed::YouTube)->create();
        $this->assertSame($baru->id, $akunBaru->periode_id);
        $this->assertSame([$akunBaru->id], SocialLink::pluck('id')->all());
        $this->assertSame(1, $lama->socialLinks()->count());
        $this->assertTrue($akunLama->fresh()->periode->is($lama));

        $this->expectException(\App\Exceptions\PeriodeDitolak::class);
        $akunLama->fresh()->update(['nama_akun' => '@ubah']);
    }

    public function test_tautan_tidak_sah_terdeteksi(): void
    {
        OsisPeriod::factory()->aktif()->create();

        $this->assertFalse(SocialLink::factory()->make(['tautan' => 'https://instagram.com.jahat.test/x'])->tautanSah());
        $this->assertFalse(SocialLink::factory()->make(['tautan' => 'http://www.instagram.com/x'])->tautanSah());
    }
}
