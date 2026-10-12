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

    public static function lupakan(): void
    {
        Cache::forget('umpan-notifikasi');
    }

    private function pengingatPemilihan(): array
    {
        $e = Election::aktif();

        if (! $e) {
            return [];
        }

        $hasil = [];
        $tanggal = fn (Carbon $t) => $t->translatedFormat('l, j F Y \p\u\k\u\l H:i').' WIB';
        $buatItem = fn (string $kunci, string $judul, string $isi, Carbon $waktu, string $tautan, bool $penting = false) => [
            'id' => "pemilihan-{$e->id}-{$kunci}",
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

            $hasil[] = $buatItem('buka', 'Bilik suara sudah dibuka',
                'Gunakan hak pilihmu sebelum '.$tanggal($e->selesai).'. Cukup masukkan kode pilih di Bilik Suara.', $e->mulai, route('bilik-suara'), true);

            // Tiga jam sebelum ditutup.
            if (now()->lt($e->selesai)) {
                $hasil[] = $buatItem('tutup', 'Bilik suara segera ditutup',
                    'Pemungutan suara berakhir '.$tanggal($e->selesai).'. Belum memilih? Masih ada waktu.', $e->selesai->copy()->subHours(3), route('bilik-suara'), true);
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
