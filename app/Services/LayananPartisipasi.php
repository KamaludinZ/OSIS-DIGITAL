<?php

namespace App\Services;

use App\Models\Election;
use App\Models\Voter;
use Illuminate\Support\Facades\DB;

/**
 * Partisipasi pemilih per kelompok (kelas siswa & GTK) untuk panitia.
 * Hanya menghitung siapa yang SUDAH memilih — tidak pernah pilihan siapa.
 */
class LayananPartisipasi
{
    /**
     * @return array{total: int, memilih: int, kelompok: list<array{nama: string, total: int, memilih: int}>}
     */
    public function ringkasan(?Election $pemilihan): array
    {
        $baris = Voter::query()
            ->terverifikasi()
            ->leftJoin('votes', fn ($j) => $j->on('votes.voter_id', '=', 'voters.id')->where('votes.election_id', $pemilihan?->id ?? 0))
            ->selectRaw("case when voters.jenis = 'gtk' then 'GTK' else coalesce(voters.kelas, 'Tanpa kelas') end as kelompok")
            ->selectRaw('count(voters.id) as total, count(votes.id) as memilih')
            ->groupBy(DB::raw("case when voters.jenis = 'gtk' then 'GTK' else coalesce(voters.kelas, 'Tanpa kelas') end"))
            ->get()
            ->map(fn ($r) => ['nama' => $r->kelompok, 'total' => (int) $r->total, 'memilih' => (int) $r->memilih])
            ->sortBy(fn ($r) => [$r['nama'] === 'GTK' ? 1 : 0, $this->urutKelas($r['nama'])])
            ->values()
            ->all();

        return [
            'total' => array_sum(array_column($baris, 'total')),
            'memilih' => array_sum(array_column($baris, 'memilih')),
            'kelompok' => $baris,
        ];
    }

    /** Urutkan VII-A < VII-B < VIII-A < IX-A. */
    private function urutKelas(string $kelas): string
    {
        $tingkat = ['VII' => 1, 'VIII' => 2, 'IX' => 3][strtok($kelas, '-')] ?? 9;

        return $tingkat.'-'.$kelas;
    }

    /**
     * Nama pemilih terverifikasi yang BELUM memilih, dikelompokkan per kelas/GTK.
     *
     * @return array<string, list<string>>
     */
    public function belumMemilih(?Election $pemilihan): array
    {
        return Voter::query()
            ->terverifikasi()
            ->whereDoesntHave('votes', fn ($v) => $v->where('election_id', $pemilihan?->id ?? 0))
            ->orderBy('nama')
            ->get(['jenis', 'kelas', 'nama'])
            ->groupBy(fn (Voter $v) => $v->jenis->value === 'gtk' ? 'GTK' : ($v->kelas ?? 'Tanpa kelas'))
            ->map(fn ($g) => $g->pluck('nama')->all())
            ->all();
    }
}
