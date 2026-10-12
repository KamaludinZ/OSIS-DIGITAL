<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Event;
use App\Models\WorkProgram;
use Illuminate\Support\Str;

/**
 * Sumber data publikasi OSIM (pengumuman, agenda, program kerja) untuk
 * antarmuka siswa. Mengembalikan array sederhana untuk state Livewire.
 */
class LayananPublikasi
{
    /** Pengumuman yang sudah tayang, terbaru dulu. */
    public function pengumumanTerbaru(?int $batas = null): array
    {
        return Announcement::tayang()
            ->with('penulis:id,name')
            ->when($batas, fn ($q) => $q->limit($batas))
            ->get()
            ->map(fn (Announcement $item) => $this->pengumumanKeArray($item))
            ->all();
    }

    /** Detail satu pengumuman; draf/terjadwal tidak bisa dibuka siswa. */
    public function cariPengumuman(int $id): ?array
    {
        $item = Announcement::tayang()->with('penulis:id,name')->find($id);

        return $item ? $this->pengumumanKeArray($item, lengkap: true) : null;
    }

    /** Kegiatan yang belum selesai, terdekat dulu. */
    public function agendaMendatang(?int $batas = null): array
    {
        return Event::mendatang()
            ->with('divisi:id,nama')
            ->when($batas, fn ($q) => $q->limit($batas))
            ->get()
            ->map(fn (Event $item) => $this->kegiatanKeArray($item))
            ->all();
    }

    /** Detail kegiatan (termasuk yang sudah lewat, untuk arsip). */
    public function cariKegiatan(int $id): ?array
    {
        $item = Event::with('divisi:id,nama')->find($id);

        return $item ? $this->kegiatanKeArray($item) + ['deskripsi' => $item->deskripsi] : null;
    }

    /**
     * Program kerja unggulan untuk sorotan beranda: yang sedang berjalan
     * dulu, lalu yang selesai, lalu rencana.
     */
    public function programKerjaUnggulan(?int $batas = 6): array
    {
        return WorkProgram::query()
            ->where('unggulan', true)
            ->with('divisi:id,nama')
            ->orderByRaw("case status when 'jalan' then 0 when 'selesai' then 1 else 2 end")
            ->orderBy('nama')
            ->when($batas, fn ($q) => $q->limit($batas))
            ->get()
            ->map(fn (WorkProgram $item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'deskripsi' => $item->deskripsi,
                'divisi' => $item->divisi?->nama ?? 'Pengurus OSIM',
                'status' => $item->status->value,
                'unggulan' => true,
                'capaian' => $item->capaian,
            ])
            ->all();
    }

    /**
     * Cari pengumuman tayang & kegiatan (termasuk yang lama) berdasarkan
     * kata kunci, tidak peka huruf besar/kecil.
     *
     * @return array{pengumuman: array, kegiatan: array}
     */
    public function cari(string $kataKunci, int $batas = 20): array
    {
        $kataKunci = Str::squish($kataKunci);

        if (mb_strlen($kataKunci) < 2) {
            return ['pengumuman' => [], 'kegiatan' => []];
        }

        $pola = '%'.addcslashes($kataKunci, '%_\\').'%';

        $pengumuman = Announcement::tayang()
            ->with('penulis:id,name')
            ->where(fn ($q) => $q
                ->whereLike('judul', $pola)
                ->orWhereLike('ringkasan', $pola)
                ->orWhereLike('isi', $pola)
                ->orWhereLike('kategori', $pola)
                ->orWhereLike('penulis_label', $pola))
            ->limit($batas)
            ->get()
            ->map(fn (Announcement $item) => $this->pengumumanKeArray($item))
            ->all();

        $kegiatan = Event::query()
            ->with('divisi:id,nama')
            ->where(fn ($q) => $q
                ->whereLike('nama', $pola)
                ->orWhereLike('deskripsi', $pola)
                ->orWhereLike('tempat', $pola)
                ->orWhereHas('divisi', fn ($d) => $d->whereLike('nama', $pola)))
            ->orderByDesc('tanggal_mulai')
            ->limit($batas)
            ->get()
            ->map(fn (Event $item) => $this->kegiatanKeArray($item))
            ->all();

        return ['pengumuman' => $pengumuman, 'kegiatan' => $kegiatan];
    }

    private function kegiatanKeArray(Event $item): array
    {
        return [
            'id' => $item->id,
            'nama' => $item->nama,
            'tanggal_mulai' => $item->tanggal_mulai->toDateString(),
            'tanggal_selesai' => $item->tanggal_selesai->toDateString(),
            'tempat' => $item->tempat ?? '-',
            'divisi' => $item->divisi?->nama ?? 'Pengurus OSIM',
        ];
    }

    private function pengumumanKeArray(Announcement $item, bool $lengkap = false): array
    {
        $data = [
            'id' => $item->id,
            'judul' => $item->judul,
            'kategori' => $item->kategori,
            'ringkasan' => $item->ringkasan ?: Str::limit(Str::squish($item->isi), 160),
            'penulis' => $item->penulis_label ?: ($item->penulis?->name ?? 'Pengurus OSIM'),
            'tanggal' => ($item->dipublikasikan_pada ?? $item->created_at)->toDateString(),
        ];

        if ($lengkap) {
            // Paragraf dipisah baris kosong.
            $data['isi'] = collect(preg_split('/\R{2,}/', trim($item->isi)))
                ->map(fn ($p) => trim($p))
                ->filter()
                ->values()
                ->all();
        }

        return $data;
    }
}
