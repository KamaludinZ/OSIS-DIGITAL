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

        $mulai = $e->mulai->format('YmdHi');
        $this->assertSame(['pemilihan-'.$e->id.'-segera-'.$mulai], collect($this->umpan())->pluck('id')->all());

        $this->travelTo($e->mulai->copy()->addHour());
        // Setelah dibuka, "segera dibuka" tidak relevan lagi.
        $this->assertSame(['pemilihan-'.$e->id.'-buka-'.$mulai], collect($this->umpan())->pluck('id')->all());

        // Bilik 6 jam: "segera ditutup" 90 menit sebelumnya.
        $this->travelTo($e->selesai->copy()->subHours(2));
        $this->assertSame('pemilihan-'.$e->id.'-buka-'.$mulai, $this->umpan()[0]['id']);
        $this->travelTo($e->selesai->copy()->subMinutes(90));
        $this->assertSame('Bilik suara ditutup 1 jam 30 menit lagi', $this->umpan()[0]['judul']);
        $this->assertSame('pemilihan-'.$e->id.'-tutup-'.$e->selesai->format('YmdHi'), $this->umpan()[0]['id']);
        $this->assertTrue($this->umpan()[0]['penting']);

        // Setelah ditutup, "sudah dibuka" & "segera ditutup" tidak tampil lagi.
        $this->travelTo($e->selesai->copy()->addMinute());
        $this->assertSame([], collect($this->umpan())->where('jenis', 'pemilihan')->all());
    }

    public function test_pemilihan_dijadwalkan_jauh_hari_tidak_memicu_pengingat_dini(): void
    {
        // Kasus laporan: pemilihan 20 Okt 08:00–12:00 dijadwalkan pada 16 Okt sore.
        \App\Models\User::factory()->create(['role' => \App\Enums\Peran::Panitia]);
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-16 15:30'));
        $e = Election::factory()->create(['mulai' => '2026-10-20 08:00', 'selesai' => '2026-10-20 12:00', 'status' => StatusPemilihan::Berjalan]);

        $pertama = [];
        for ($t = now()->copy(); $t->lte(\Illuminate\Support\Carbon::parse('2026-10-20 12:30')); $t->addMinutes(15)) {
            $this->travelTo($t->copy());
            foreach (app(LayananNotifikasi::class)->kirimPengingatPemilihan() as $kunci) {
                $pertama[strtok($kunci, '@')] ??= $t->format('Y-m-d H:i');
            }
            foreach ($this->umpan() as $n) {
                $pertama['umpan:'.explode('-', $n['id'])[2]] ??= $t->format('Y-m-d H:i');
            }
        }

        $this->assertSame([
            'umpan:segera' => '2026-10-17 08:00', // H-3 di umpan publik
            'segera' => '2026-10-19 08:00',       // H-1 untuk akun
            'buka' => '2026-10-20 08:00',
            'umpan:buka' => '2026-10-20 08:00',
            // Bilik hanya 4 jam: "segera ditutup" 1 jam sebelum tutup, bukan 09:00.
            'tutup' => '2026-10-20 11:00',
            'umpan:tutup' => '2026-10-20 11:00',
        ], $pertama);
        $this->assertSame('Bilik suara ditutup 1 jam lagi', \Illuminate\Notifications\DatabaseNotification::latest()->first()->data['title']);
    }

    public function test_jadwal_diubah_menarik_pengingat_lama_dari_lonceng(): void
    {
        $panitia = \App\Models\User::factory()->create(['role' => \App\Enums\Peran::Panitia]);
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-16 14:30'));
        // Tersimpan dulu dengan jadwal keliru (hari ini 08:00–16:00) …
        $e = Election::factory()->create(['mulai' => '2026-10-16 08:00', 'selesai' => '2026-10-16 16:00', 'status' => StatusPemilihan::Berjalan]);
        app(LayananNotifikasi::class)->kirimPengingatPemilihan();
        $this->assertSame(['Bilik suara ditutup 2 jam lagi'], $panitia->notifications()->get()->pluck('data.title')->all());

        // … lalu dibetulkan ke 20 Oktober: pengingat basi hilang, tidak ada yang baru sebelum waktunya.
        $e->update(['mulai' => '2026-10-20 08:00', 'selesai' => '2026-10-20 12:00']);
        $this->assertSame(0, $panitia->notifications()->count());
        $this->assertSame([], $e->fresh()->pengingat_terkirim);
        $this->assertSame([], app(LayananNotifikasi::class)->kirimPengingatPemilihan());

        // Status kembali draf juga menarik pengingat yang sudah terkirim.
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-19 09:00'));
        app(LayananNotifikasi::class)->kirimPengingatPemilihan();
        $this->assertSame(1, $panitia->notifications()->count());
        $e->update(['status' => StatusPemilihan::Draft]);
        $this->assertSame(0, $panitia->notifications()->count());
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

    public function test_penjadwal_pengingat_pemilihan(): void
    {
        $this->travelTo(now()->setTime(8, 0));
        $panitia = \App\Models\User::factory()->create(['role' => \App\Enums\Peran::Panitia]);
        $tidakMau = \App\Models\User::factory()->create(['role' => \App\Enums\Peran::Pengurus]);
        $tidakMau->aturNotifikasi('pemilihan', false);
        $e = Election::factory()->create(['mulai' => now()->addDays(2), 'selesai' => now()->addDays(2)->addHours(8), 'status' => StatusPemilihan::Berjalan]);
        $kirim = fn () => app(LayananNotifikasi::class)->kirimPengingatPemilihan();

        $this->assertSame([], $kirim()); // masih jauh

        $this->travelTo($e->mulai->copy()->subHours(20));
        $this->assertSame(['segera@'.$e->mulai->toDateTimeString()], $kirim());
        $this->assertSame([], $kirim()); // tidak dobel

        $this->travelTo($e->mulai->copy()->addMinutes(5));
        $this->assertSame(['buka@'.$e->mulai->toDateTimeString()], $kirim());

        $this->travelTo($e->selesai->copy()->subHours(2));
        $this->artisan('osim:kirim-pengingat-pemilihan')->expectsOutputToContain('1 pengingat');

        // Bilik 8 jam: "segera ditutup" 2 jam sebelumnya (seperempat lama dibuka, maks. 3 jam).
        $this->assertSame(['Bilik suara ditutup 2 jam lagi', 'Bilik suara sudah dibuka', 'Besok bilik suara dibuka'],
            $panitia->notifications()->orderByDesc('created_at')->get()->pluck('data.title')->all());
        $this->assertSame(0, $tidakMau->notifications()->count());

        // Jadwal diundur panitia: pengingat "tutup" berlaku lagi untuk jam yang baru.
        $e->update(['selesai' => $e->selesai->copy()->addHours(4)]);
        $this->travelTo($e->fresh()->selesai->copy()->subHour());
        $this->assertSame(['tutup@'.$e->fresh()->selesai->toDateTimeString()], $kirim());
    }

    public function test_migrasi_menghapus_pengingat_lama_tanpa_penanda(): void
    {
        $akun = \App\Models\User::factory()->create(['role' => \App\Enums\Peran::Panitia]);
        $buat = fn (array $data) => $akun->notifications()->create(['id' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'Filament', 'data' => $data]);
        $buat(['title' => 'Bilik suara ditutup 3 jam lagi', 'viewData' => []]);
        $baru = $buat(['title' => 'Bilik suara sudah dibuka', 'viewData' => ['pemilihan' => '1', 'pengingat' => 'buka@2026-10-20 08:00:00']]);
        $lain = $buat(['title' => 'Pengumuman baru: Lomba', 'viewData' => []]);

        (require database_path('migrations/2026_10_18_000100_tarik_pengingat_pemilihan_lama.php'))->up();

        $this->assertEqualsCanonicalizing([$baru->id, $lain->id], $akun->notifications()->pluck('id')->all());
    }
}
