<?php

namespace Tests\Feature;

use App\Enums\StatusPemilihan;
use App\Models\Announcement;
use App\Models\Election;
use App\Models\Event;
use App\Services\LayananNotifikasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private function umpan(): array
    {
        LayananNotifikasi::lupakan();

        return app(LayananNotifikasi::class)->umpan();
    }

    public function test_pengingat_pemilihan_mengikuti_jadwal(): void
    {
        $this->travelTo(now()->setTime(9, 0));
        $e = Election::factory()->create(['mulai' => now()->addDays(2), 'selesai' => now()->addDays(2)->addHours(6), 'status' => StatusPemilihan::Berjalan]);

        $this->assertSame(['pemilihan-'.$e->id.'-segera'], collect($this->umpan())->pluck('id')->all());

        $this->travelTo($e->mulai->copy()->addHour());
        // Setelah dibuka, "segera dibuka" tidak relevan lagi.
        $this->assertSame(['pemilihan-'.$e->id.'-buka'], collect($this->umpan())->pluck('id')->all());

        $this->travelTo($e->selesai->copy()->subHours(2));
        $this->assertSame('pemilihan-'.$e->id.'-tutup', $this->umpan()[0]['id']);
        $this->assertTrue($this->umpan()[0]['penting']);
    }

    public function test_pengumuman_baru_dan_agenda_sepekan(): void
    {
        $tayang = Announcement::factory()->create(['dipublikasikan' => true, 'dipublikasikan_pada' => now()->subHour(), 'judul' => 'Lomba Adzan']);
        Announcement::factory()->create(['dipublikasikan' => false, 'judul' => 'Masih draf']);
        Announcement::factory()->create(['dipublikasikan' => true, 'dipublikasikan_pada' => now()->addDay(), 'judul' => 'Terjadwal besok']);
        $besok = Event::factory()->create(['nama' => 'Jalan Sehat', 'tanggal_mulai' => today()->addDay(), 'tanggal_selesai' => today()->addDay()]);
        Event::factory()->create(['nama' => 'Jauh', 'tanggal_mulai' => today()->addDays(20), 'tanggal_selesai' => today()->addDays(20)]);

        $umpan = collect($this->umpan());
        $this->assertTrue($umpan->contains('id', 'pengumuman-'.$tayang->id));
        $this->assertFalse($umpan->contains('judul', 'Masih draf'));
        $this->assertFalse($umpan->contains('judul', 'Terjadwal besok'));
        $this->assertSame('Besok: Jalan Sehat', $umpan->firstWhere('id', 'kegiatan-'.$besok->id)['judul']);
        $this->assertFalse($umpan->contains(fn ($n) => str_contains($n['judul'], 'Jauh')));

        // Halaman & lonceng tampil untuk tamu, dengan umpan dikirim ke perangkat.
        $this->get('/notifikasi')->assertOk()->assertSee('Pemberitahuan')->assertSee('Atur pemberitahuan');
        $this->getJson('/notifikasi/umpan')->assertOk()->assertJsonFragment(['judul' => 'Lomba Adzan', 'jenis' => 'pengumuman']);
        $this->get('/')->assertSee('aria-label="Pemberitahuan"', false)->assertSee(route('notifikasi.umpan'), false);
    }

    public function test_umpan_diperbarui_saat_pengumuman_disimpan(): void
    {
        $this->assertSame([], $this->umpan());
        Announcement::factory()->create(['dipublikasikan' => true, 'dipublikasikan_pada' => now()->subMinute(), 'judul' => 'Kabar segar']);
        $this->assertSame('Kabar segar', app(LayananNotifikasi::class)->umpan()[0]['judul']);
    }

    public function test_detail_pengumuman_menandai_pemberitahuan_dibaca(): void
    {
        $a = Announcement::factory()->create(['dipublikasikan' => true, 'dipublikasikan_pada' => now()->subHour()]);
        $k = Event::factory()->create(['tanggal_mulai' => today()->addDay(), 'tanggal_selesai' => today()->addDay()]);

        $this->get('/pengumuman/'.$a->id)->assertOk()->assertSee("\$store.notif.tandai('pengumuman-{$a->id}')", false);
        $this->get('/kegiatan/'.$k->id)->assertOk()->assertSee("\$store.notif.tandai('kegiatan-{$k->id}')", false);

        // Tautan di umpan menuju halaman detail yang sama.
        $umpan = collect($this->umpan());
        $this->assertSame(route('pengumuman.detail', $a->id), $umpan->firstWhere('id', 'pengumuman-'.$a->id)['tautan']);
        $this->assertSame(route('kegiatan.detail', $k->id), $umpan->firstWhere('id', 'kegiatan-'.$k->id)['tautan']);
    }
}
