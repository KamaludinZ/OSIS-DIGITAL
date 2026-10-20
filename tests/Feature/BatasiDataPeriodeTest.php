<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\Event;
use App\Models\Member;
use App\Models\OsisPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatasiDataPeriodeTest extends TestCase
{
    use RefreshDatabase;

    private OsisPeriod $arsip;

    private OsisPeriod $aktif;

    private OsisPeriod $disiapkan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->arsip = OsisPeriod::factory()->tahun(2025)->arsip()->create();
        $this->aktif = OsisPeriod::factory()->tahun(2026)->aktif()->create(['mode_pemilihan' => true]);
        $this->disiapkan = OsisPeriod::factory()->tahun(2027)->create();
    }

    public function test_kegiatan_periode_aktif_dan_arsip_terbuka_periode_disiapkan_tidak(): void
    {
        $sekarang = Event::factory()->create(['periode_id' => $this->aktif->id]);
        $depan = Event::factory()->create(['periode_id' => $this->disiapkan->id]);
        // Data arsip tidak bisa ditambah lewat model; dibuat langsung seolah tercatat saat periode itu aktif.
        $lama = Event::semuaPeriode()->find(\Illuminate\Support\Facades\DB::table('events')->insertGetId([
            'periode_id' => $this->arsip->id, 'nama' => 'Kegiatan Lama', 'tanggal_mulai' => now()->subYear()->toDateString(),
            'tanggal_selesai' => now()->subYear()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]));

        $this->get('/kegiatan/'.$sekarang->id)->assertOk();
        $this->get('/kegiatan/'.$lama->id)->assertOk();
        $this->get('/kegiatan/'.$depan->id)->assertNotFound();
    }

    public function test_profil_kandidat_periode_lain_tidak_bisa_dibuka_langsung(): void
    {
        $lama = \App\Models\Candidate::factory()->create(['periode_id' => $this->arsip->id]);

        $this->get('/kandidat/'.$lama->id)->assertNotFound();
    }

    public function test_ruang_osim_menolak_record_periode_lain(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        $sekarang = Member::factory()->create(['periode' => '2026/2027', 'periode_id' => $this->aktif->id]);
        $lama = Member::factory()->create(['periode' => '2025/2026', 'periode_id' => $this->arsip->id]);

        $this->get('/admin/anggota/'.$sekarang->id.'/edit')->assertOk();
        $this->get('/admin/anggota/'.$lama->id.'/edit')->assertNotFound();
    }

    public function test_perubahan_data_arsip_lewat_alamat_langsung_ditolak(): void
    {
        $lama = \App\Models\SocialPost::factory()->create(['periode_id' => $this->arsip->id]);

        \Illuminate\Support\Facades\Route::middleware(['web', 'periode:'.\App\Models\SocialPost::class.',post,arsip'])
            ->post('/uji-periode/{post}', fn () => 'ok');

        $this->post('/uji-periode/'.$lama->id)->assertForbidden();
    }
}
