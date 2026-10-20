<?php

namespace Tests\Feature;

use App\Models\OsisPeriod;
use App\Models\SocialLink;
use App\Models\SocialPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BackfillSosmedTest extends TestCase
{
    use RefreshDatabase;

    private function jalankanBackfill(): void
    {
        (require database_path('migrations/2026_10_20_000800_backfill_sosmed_ke_periode_aktif.php'))->up();
    }

    public function test_konten_lama_dan_akun_tiktok_masuk_periode_aktif(): void
    {
        $lama = SocialPost::factory()->create(['platform' => 'tiktok', 'tautan' => 'https://www.tiktok.com/@osim.mtsn2/video/7000000000000000001']);
        $this->assertNull($lama->periode_id);

        $aktif = OsisPeriod::factory()->tahun(2026)->aktif()->create();
        $this->jalankanBackfill();
        $this->jalankanBackfill(); // aman diulang

        $this->assertSame($aktif->id, $lama->fresh()->periode_id);
        $akun = SocialLink::sole();
        $this->assertSame('@osim.mtsn2', $akun->nama_akun);
        $this->assertSame('https://www.tiktok.com/@osim.mtsn2', $akun->tautan);
        $this->assertTrue($akun->tautanSah());
    }

    public function test_tanpa_periode_aktif_tidak_mengubah_apa_pun(): void
    {
        $post = SocialPost::factory()->create();
        $this->jalankanBackfill();

        $this->assertNull($post->fresh()->periode_id);
        $this->assertSame(0, DB::table('social_links')->count());
    }

    public function test_konten_baru_otomatis_tercatat_di_periode_aktif(): void
    {
        $aktif = OsisPeriod::factory()->tahun(2026)->aktif()->create();

        $this->assertSame($aktif->id, SocialPost::factory()->create()->periode_id);
    }

    public function test_seeder_menyiapkan_periode_aktif_dan_akun_contoh(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->seed([\Database\Seeders\PeriodeSeeder::class, \Database\Seeders\SosmedSeeder::class, \Database\Seeders\PeriodeSeeder::class]);

        $this->assertSame(1, OsisPeriod::status(\App\Enums\StatusPeriode::Aktif)->count());
        $this->assertTrue(OsisPeriod::aktif()->mode_pemilihan);
        $this->assertSame(3, SocialLink::count());
        $this->assertSame(OsisPeriod::aktif()->id, SocialPost::query()->value('periode_id'));
    }
}
