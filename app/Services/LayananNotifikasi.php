<?php

namespace App\Services;

use App\Enums\StatusPemilihan;
use App\Models\Announcement;
use App\Models\Election;
use App\Models\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Umpan pemberitahuan publik OSIM. Siswa & GTK tidak punya akun, jadi
 * pemberitahuan disusun dari data publik dan status "dibaca" serta
 * preferensinya disimpan di perangkat masing-masing (localStorage).
 */
class LayananNotifikasi
{
    public const JENIS = [
        'pengumuman' => ['Pengumuman baru', 'megaphone'],
        'pemilihan' => ['Pengingat pemilihan', 'check-badge'],
        'kegiatan' => ['Agenda kegiatan', 'calendar-days'],
    ];

    /**
     * @return list<array{id: string, jenis: string, judul: string, isi: string, waktu: string, tautan: string, penting: bool}>
     */
    public function umpan(): array
    {
        return Cache::remember('umpan-notifikasi', 60, fn () => collect()
            ->merge($this->pengingatPemilihan())
            ->merge($this->pengumuman())
            ->merge($this->kegiatan())
            ->filter(fn ($n) => Carbon::parse($n['waktu'])->lte(now()))
            ->sortByDesc('waktu')
            ->values()
            ->all());
    }

    /**
     * Akun aktif (staf & kandidat) yang mau menerima jenis pemberitahuan ini.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\User>
     */
    public function penerima(string $jenis, ?int $kecuali = null): \Illuminate\Support\Collection
    {
        return \App\Models\User::query()
            ->where('aktif', true)
            ->whereIn('role', ['admin', 'pengurus', 'panitia', 'kandidat'])
            ->when($kecuali, fn ($q) => $q->whereKeyNot($kecuali))
            ->with('preferensiNotifikasi')
            ->get()
            ->filter(fn ($u) => $u->mauMenerima($jenis))
            ->values();
    }

    /**
     * Kirim "pengumuman baru" ke akun sekali saja, saat pengumuman sudah tayang.
     * Penandanya diklaim lewat update bersyarat agar aman dari dua pemicu bersamaan.
     */
    public function beritahuPengumuman(Announcement $a): int
    {
        if ($a->diberitahukan_pada || ! $a->sudahTayang()) {
            return 0;
        }

        $diklaim = Announcement::whereKey($a->id)->whereNull('diberitahukan_pada')->update(['diberitahukan_pada' => now()]);

        if (! $diklaim) {
            return 0;
        }

        $a->diberitahukan_pada = now();
        $penerima = $this->penerima('pengumuman', $a->penulis_id);

        \Filament\Notifications\Notification::make()
            ->title('Pengumuman baru: '.$a->judul)
            ->body((string) str($a->ringkasan ?: strip_tags((string) $a->isi))->squish()->limit(140))
            ->icon('heroicon-o-megaphone')
            ->actions([\Filament\Actions\Action::make('baca')->label('Baca')->url(route('pengumuman.detail', $a->id))])
            ->sendToDatabase($penerima);

        return $penerima->count();
    }

    /** Pengumuman terjadwal yang baru saja tayang (dipanggil penjadwal tiap menit). */
    public function kirimPengumumanTerjadwal(): int
    {
        return Announcement::query()
            ->where('dipublikasikan', true)
            ->whereNull('diberitahukan_pada')
            ->where('dipublikasikan_pada', '<=', now())
            ->where('dipublikasikan_pada', '>=', now()->subDays(2)) // jangan menyusulkan kabar basi
            ->get()
            ->sum(fn (Announcement $a) => $this->beritahuPengumuman($a));
    }

    /**
     * Berapa menit sebelum ditutup pengingat "segera ditutup" muncul: 3 jam, tetapi
     * tidak lebih dari seperempat lama bilik suara dibuka (min. 30 menit). Bilik
     * 08:00–12:00 diingatkan pukul 11:00, bukan 09:00 (baru sejam dibuka).
     */
    public static function jedaTutup(Election $e): int
    {
        return (int) max(30, min(180, intdiv((int) $e->mulai->diffInMinutes($e->selesai), 4)));
    }

    /** "3 jam", "1 jam 15 menit", "45 menit". */
    public static function labelJeda(int $menit): string
    {
        return trim((intdiv($menit, 60) ? intdiv($menit, 60).' jam ' : '').($menit % 60 ? ($menit % 60).' menit' : ''));
    }

    /** Kunci pengingat yang berlaku untuk jadwal pemilihan saat ini. */
    public static function kunciPengingat(Election $e): array
    {
        return ['segera@'.$e->mulai->toDateTimeString(), 'buka@'.$e->mulai->toDateTimeString(), 'tutup@'.$e->selesai->toDateTimeString()];
    }

    /**
     * Jadwal atau status pemilihan berubah: tarik pengingat akun yang tidak sesuai lagi
     * (mis. "ditutup 3 jam lagi" dari jadwal lama) agar lonceng tidak menampilkan kabar basi.
     */
    public function tarikPengingatBasi(Election $e): int
    {
        $berlaku = $e->status === StatusPemilihan::Berjalan ? self::kunciPengingat($e) : [];

        $basi = \Illuminate\Notifications\DatabaseNotification::query()
            ->where('data->viewData->pemilihan', (string) $e->id)
            ->get()
            ->reject(fn ($n) => in_array($n->data['viewData']['pengingat'] ?? null, $berlaku, true));

        \Illuminate\Notifications\DatabaseNotification::whereKey($basi->modelKeys())->delete();

        $e->forceFill(['pengingat_terkirim' => array_values(array_intersect($e->pengingat_terkirim ?? [], $berlaku))])->saveQuietly();

        return $basi->count();
    }

    /**
     * Pengingat pemilihan untuk akun (dipanggil penjadwal). Tiap pengingat
     * dikirim sekali per jadwal; bila jam buka/tutup diubah, kuncinya ikut berubah.
     *
     * @return list<string> kunci pengingat yang baru dikirim
     */
    public function kirimPengingatPemilihan(): array
    {
        $terkirim = [];

        foreach (Election::query()->where('status', StatusPemilihan::Berjalan)->where('selesai', '>', now())->get() as $e) {
            $jam = fn (Carbon $t) => $t->translatedFormat('l, j F Y \p\u\k\u\l H:i').' WIB';
            $jeda = self::jedaTutup($e);
            [$kunciSegera, $kunciBuka, $kunciTutup] = self::kunciPengingat($e);
            $jadwal = [
                // [kunci, mulai berlaku, berakhir, judul, isi]
                [$kunciSegera, $e->mulai->copy()->subDay(), $e->mulai,
                    'Besok bilik suara dibuka', $e->judul.' dibuka '.$jam($e->mulai).'. Pastikan kartu pemilih sudah dibagikan.'],
                [$kunciBuka, $e->mulai, $e->selesai->copy()->subMinutes($jeda),
                    'Bilik suara sudah dibuka', 'Pemungutan suara berlangsung sampai '.$jam($e->selesai).'.'],
                [$kunciTutup, $e->selesai->copy()->subMinutes($jeda), $e->selesai,
                    'Bilik suara ditutup '.self::labelJeda($jeda).' lagi', 'Pemungutan suara berakhir '.$jam($e->selesai).'. Ingatkan yang belum memilih.'],
            ];

            $sudah = $e->pengingat_terkirim ?? [];

            foreach ($jadwal as [$kunci, $dari, $sampai, $judul, $isi]) {
                if (in_array($kunci, $sudah, true) || now()->lt($dari) || now()->gte($sampai)) {
                    continue;
                }

                $sudah[] = $kunci;
                $e->forceFill(['pengingat_terkirim' => $sudah])->saveQuietly();

                \Filament\Notifications\Notification::make()->title($judul)->body($isi)
                    ->icon('heroicon-o-check-badge')
                    // Penanda agar pengingat bisa ditarik bila jadwal berubah (tarikPengingatBasi).
                    ->viewData(['pemilihan' => (string) $e->id, 'pengingat' => $kunci])
                    ->actions([\Filament\Actions\Action::make('buka')->label('Lihat')->url(route('bilik-suara'))])
                    ->sendToDatabase($this->penerima('pemilihan'));

                $terkirim[] = $kunci;
            }
        }

        return $terkirim;
    }

    public static function lupakan(): void
    {
        Cache::forget('umpan-notifikasi');
    }

    private function pengingatPemilihan(): array
    {
        $e = Election::aktif();

        // Di luar masa pemilihan, tautan bilik suara/kandidat/hasil terkunci: jangan diumumkan.
        if (! $e || ! app(LayananModePemilihan::class)->aktif()) {
            return [];
        }

        $hasil = [];
        $tanggal = fn (Carbon $t) => $t->translatedFormat('l, j F Y \p\u\k\u\l H:i').' WIB';
        // Id memuat jam jadwal: bila jadwal diubah, perangkat menganggapnya pemberitahuan baru
        // (tanda "dibaca" & notifikasi layar dari jadwal lama tidak menahan yang baru).
        $buatItem = fn (string $kunci, string $judul, string $isi, Carbon $waktu, string $tautan, bool $penting = false) => [
            'id' => "pemilihan-{$e->id}-{$kunci}-".($kunci === 'tutup' ? $e->selesai : $e->mulai)->format('YmdHi'),
            'jenis' => 'pemilihan',
            'judul' => $judul,
            'isi' => $isi,
            'waktu' => $waktu->toDateTimeString(),
            'tautan' => $tautan,
            'penting' => $penting,
        ];

        if ($e->status === StatusPemilihan::Berjalan) {
            // Tiga hari sebelum dibuka.
            if (now()->lt($e->mulai)) {
                $hasil[] = $buatItem('segera', 'Pemilihan ketua OSIM segera dibuka',
                    'Bilik suara dibuka '.$tanggal($e->mulai).'. Siapkan kartu pemilih berisi kode 6 angka.', $e->mulai->copy()->subDays(3), route('kandidat.index'));
            }

            // Menjelang ditutup (lihat jedaTutup); keduanya hanya selama bilik masih dibuka.
            if (now()->lt($e->selesai)) {
                $hasil[] = $buatItem('buka', 'Bilik suara sudah dibuka',
                    'Gunakan hak pilihmu sebelum '.$tanggal($e->selesai).'. Cukup masukkan kode pilih di Bilik Suara.', $e->mulai, route('bilik-suara'), true);
                $hasil[] = $buatItem('tutup', 'Bilik suara ditutup '.self::labelJeda(self::jedaTutup($e)).' lagi',
                    'Pemungutan suara berakhir '.$tanggal($e->selesai).'. Belum memilih? Masih ada waktu.', $e->selesai->copy()->subMinutes(self::jedaTutup($e)), route('bilik-suara'), true);
            }
        }

        if ($e->status === StatusPemilihan::Selesai && $e->hasil_publik) {
            $hasil[] = $buatItem('hasil', 'Hasil pemilihan sudah diumumkan',
                'Panitia telah mempublikasikan perolehan suara '.$e->judul.'.', $e->updated_at, route('hasil'), true);
        }

        return $hasil;
    }

    private function pengumuman(): array
    {
        return Announcement::query()->tayang()
            ->where(fn ($q) => $q->where('dipublikasikan_pada', '>=', now()->subDays(30))
                ->orWhere(fn ($q) => $q->whereNull('dipublikasikan_pada')->where('created_at', '>=', now()->subDays(30))))
            ->limit(15)->get()
            ->map(fn (Announcement $a) => [
                'id' => 'pengumuman-'.$a->id,
                'jenis' => 'pengumuman',
                'judul' => $a->judul,
                'isi' => (string) str($a->ringkasan ?: strip_tags((string) $a->isi))->squish()->limit(140),
                'waktu' => ($a->dipublikasikan_pada ?? $a->created_at)->toDateTimeString(),
                'tautan' => route('pengumuman.detail', $a->id),
                'penting' => false,
            ])->all();
    }

    private function kegiatan(): array
    {
        return Event::query()->mendatang()
            ->whereDate('tanggal_mulai', '<=', today()->addDays(7))
            ->limit(10)->get()
            ->map(fn (Event $k) => [
                'id' => 'kegiatan-'.$k->id,
                'jenis' => 'kegiatan',
                'judul' => match (true) {
                    $k->tanggal_mulai->isToday() || $k->tanggal_mulai->isPast() => 'Hari ini: '.$k->nama,
                    $k->tanggal_mulai->isTomorrow() => 'Besok: '.$k->nama,
                    default => $k->tanggal_mulai->translatedFormat('l, j F').': '.$k->nama,
                },
                'isi' => trim(($k->tempat ? 'Di '.$k->tempat.'. ' : '').(string) str($k->deskripsi)->squish()->limit(110)),
                // Muncul seminggu sebelum kegiatan (atau saat dibuat bila lebih baru).
                'waktu' => collect([$k->tanggal_mulai->copy()->subDays(7), $k->created_at])->max()->toDateTimeString(),
                'tautan' => route('kegiatan.detail', $k->id),
                'penting' => false,
            ])->all();
    }
}
