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

    public function test_pengaturan_preferensi_pemberitahuan(): void
    {
        $this->get('/notifikasi')->assertOk()
            ->assertSee('Jenis yang ingin kamu terima')
            ->assertSeeInOrder(['Pengumuman baru', 'Pengingat pemilihan', 'Agenda kegiatan'])
            ->assertSee("\$store.notif.ubahPref('pemilihan'", false)
            ->assertSee("location.hash === '#atur'", false)
            ->assertSee('Tampilkan juga di layar perangkat')
            ->assertSee('Tersimpan di perangkat ini')
            ->assertSee('beriTahuTersimpan()', false);
    }

    public function test_tabel_pemberitahuan_akun_staf(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumns('notifications', ['id', 'type', 'notifiable_type', 'notifiable_id', 'data', 'read_at']));

        $admin = \App\Models\User::factory()->create(['role' => \App\Enums\Peran::Admin]);
        \Filament\Notifications\Notification::make()->title('Uji pemberitahuan')->sendToDatabase($admin);

        $this->assertSame(1, $admin->unreadNotifications()->count());
        $this->actingAs($admin)->followingRedirects()->get('/admin')->assertOk()->assertSee('database-notifications', false);
    }

    public function test_preferensi_notifikasi_akun(): void
    {
        $calon = \App\Models\Candidate::factory()->create();
        $kandidat = \App\Models\User::factory()->create(['role' => \App\Enums\Peran::Kandidat, 'candidate_id' => $calon->id]);
        $panitia = \App\Models\User::factory()->create(['role' => \App\Enums\Peran::Panitia]);

        $this->assertTrue($kandidat->mauMenerima('pertanyaan'));      // bawaan aktif
        $this->assertFalse($panitia->mauMenerima('pertanyaan'));      // tidak relevan untuk panitia

        $kandidat->aturNotifikasi('pertanyaan', false);
        $kandidat->aturNotifikasi('pertanyaan', false);               // idempoten
        $this->assertFalse($kandidat->fresh()->mauMenerima('pertanyaan'));
        $this->assertSame(1, \App\Models\NotificationPreference::count());

        $kandidat->delete();
        $this->assertSame(0, \App\Models\NotificationPreference::count());
    }

    public function test_daftar_dan_tanda_baca_pemberitahuan_akun(): void
    {
        $calon = \App\Models\Candidate::factory()->create();
        $kandidat = \App\Models\User::factory()->create(['role' => \App\Enums\Peran::Kandidat, 'candidate_id' => $calon->id]);
        $lain = \App\Models\User::factory()->create(['role' => \App\Enums\Peran::Admin]);
        $tujuan = route('kandidat.profil', ['id' => $calon->id, 'tab' => 'tanya-jawab']);

        \Filament\Notifications\Notification::make()->title('Pertanyaan baru untukmu')->body('Bagaimana programmu?')
            ->actions([\Filament\Actions\Action::make('buka')->url($tujuan)])->sendToDatabase($kandidat);
        \Filament\Notifications\Notification::make()->title('Kedua')->sendToDatabase($kandidat);
        \Filament\Notifications\Notification::make()->title('Milik orang lain')->sendToDatabase($lain);

        $this->actingAs($kandidat);
        $this->get('/')->assertSee('$store.notif.akunBelum = 2', false);

        $halaman = \Livewire\Volt\Volt::test('notifikasi')
            ->assertSee('Untuk akunmu')->assertSee('Pertanyaan baru untukmu')->assertDontSee('Milik orang lain');

        $id = $kandidat->notifications()->where('data->title', 'Pertanyaan baru untukmu')->value('id');
        $halaman->call('bukaAkun', $id)->assertRedirect($tujuan);
        $this->assertSame(1, $kandidat->unreadNotifications()->count());

        \Livewire\Volt\Volt::test('notifikasi')->call('bacaSemuaAkun');
        $this->assertSame(0, $kandidat->unreadNotifications()->count());

        // Tidak bisa membuka pemberitahuan akun lain.
        $milikLain = $lain->notifications()->value('id');
        \Livewire\Volt\Volt::test('notifikasi')->call('bukaAkun', $milikLain)->assertNotFound();
        $this->assertNull($lain->notifications()->first()->read_at);
    }

    public function test_pemicu_pemberitahuan_saat_pengumuman_tayang(): void
    {
        $pengurus = \App\Models\User::factory()->create(['role' => \App\Enums\Peran::Pengurus]);
        $panitia = \App\Models\User::factory()->create(['role' => \App\Enums\Peran::Panitia]);
        $tidakMau = \App\Models\User::factory()->create(['role' => \App\Enums\Peran::Admin]);
        $tidakMau->aturNotifikasi('pengumuman', false);
        \App\Models\User::factory()->create(['role' => \App\Enums\Peran::Admin, 'aktif' => false]);

        // Draf: belum ada pemberitahuan. Ditayangkan: terkirim sekali, bukan ke penulisnya.
        $a = Announcement::factory()->create(['dipublikasikan' => false, 'judul' => 'Class Meeting', 'penulis_id' => $pengurus->id]);
        $this->assertSame(0, \Illuminate\Notifications\DatabaseNotification::count());

        $a->update(['dipublikasikan' => true, 'dipublikasikan_pada' => now()]);
        $a->update(['judul' => 'Class Meeting (revisi)']);
        $this->assertSame(1, \Illuminate\Notifications\DatabaseNotification::count());
        $this->assertSame('Pengumuman baru: Class Meeting', $panitia->notifications()->first()->data['title']);
        $this->assertSame(route('pengumuman.detail', $a->id), $panitia->notifications()->first()->data['actions'][0]['url']);
        $this->assertNotNull($a->fresh()->diberitahukan_pada);

        // Terjadwal: dikirim penjadwal saat waktunya tiba.
        $b = Announcement::factory()->create(['dipublikasikan' => true, 'dipublikasikan_pada' => now()->addHour(), 'judul' => 'Besok libur']);
        $this->artisan('osim:kirim-pengumuman-terjadwal')->expectsOutputToContain('0 pemberitahuan');
        $this->travel(61)->minutes();
        $this->artisan('osim:kirim-pengumuman-terjadwal')->expectsOutputToContain('2 pemberitahuan');
        $this->artisan('osim:kirim-pengumuman-terjadwal')->expectsOutputToContain('0 pemberitahuan');
        $this->assertSame(2, $pengurus->notifications()->count() + $panitia->notifications()->where('data->title', 'Pengumuman baru: Besok libur')->count());
    }

    public function test_pertanyaan_baru_memberi_tahu_akun_kandidat(): void
    {
        $calon = \App\Models\Candidate::factory()->create();
        $lain = \App\Models\Candidate::factory()->create();
        $akun = \App\Models\User::factory()->create(['role' => \App\Enums\Peran::Kandidat, 'candidate_id' => $calon->id]);
        $akunLain = \App\Models\User::factory()->create(['role' => \App\Enums\Peran::Kandidat, 'candidate_id' => $lain->id]);
        $penanya = \App\Models\Voter::factory()->create();
        $layanan = app(\App\Services\LayananKampanye::class);

        $layanan->kirimPertanyaan($penanya, $calon, 'Bagaimana rencana kegiatan Ramadan nanti?');
        $layanan->kirimPertanyaan($penanya, $calon, 'Bagaimana rencana kegiatan Ramadan nanti?'); // duplikat tidak memberi tahu lagi

        $this->assertSame(1, $akun->notifications()->count());
        $this->assertSame('Pertanyaan baru untukmu', $akun->notifications()->first()->data['title']);
        $this->assertSame(0, $akunLain->notifications()->count());

        $akun->aturNotifikasi('pertanyaan', false);
        $layanan->kirimPertanyaan($penanya, $calon, 'Apakah ada program literasi Al-Quran?');
        $this->assertSame(1, $akun->notifications()->count());
    }
}
