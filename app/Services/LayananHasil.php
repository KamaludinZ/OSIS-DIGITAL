<?php

namespace App\Services;

use App\Enums\StatusPemilihan;
use Illuminate\Validation\ValidationException;
use App\Models\Election;
use App\Models\Voter;
use App\Models\Vote;
use Illuminate\Support\Facades\Cache;

/**
 * Agregasi perolehan suara untuk halaman Hasil. Satu query GROUP BY di atas
 * indeks (election_id, candidate_id) — tidak memuat baris suara satu per satu.
 */
class LayananHasil
{
    public function __construct(private LayananPemilihan $pemilihan) {}

    /**
     * Jumlah suara per kandidat (kandidat tanpa suara tetap muncul dengan 0),
     * urut nomor urut.
     *
     * @return array<int, array{id: int, nomor_urut: int, nama_ketua: string, nama_wakil: ?string, berpasangan: bool, nama_tampil: string, foto_url: ?string, suara: int}>
     */
    public function perolehanSuara(Election $pemilihan): array
    {
        // Dibaca dari rekap agregat (satu baris per kandidat), bukan menghitung baris suara satu per satu.
        $hitungan = \App\Models\VoteTally::query()
            ->where('election_id', $pemilihan->id)
            ->pluck('jumlah', 'candidate_id');

        return $pemilihan->candidates()
            ->get(['id', 'nomor_urut', 'nama_ketua', 'nama_wakil', 'foto_url'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'nomor_urut' => $c->nomor_urut,
                'nama_ketua' => $c->nama_ketua,
                'nama_wakil' => $c->nama_wakil,
                'berpasangan' => $c->berpasangan(),
                'nama_tampil' => $c->namaTampil(),
                'foto_url' => $c->fotoPublik(),
                'suara' => (int) ($hitungan[$c->id] ?? 0),
            ])
            ->values()
            ->all();
    }

    /** Jumlah pemilih berhak: siswa & GTK yang datanya terverifikasi. */
    public function jumlahPemilih(): int
    {
        return Voter::terverifikasi()->count();
    }

    /** Lama cache rekap (detik). Banyak siswa bisa membuka Hasil bersamaan. */
    public const CACHE_DETIK = 5;

    public static function kunciCache(int $pemilihanId): string
    {
        return "rekap-suara:{$pemilihanId}";
    }

    /** Tambah satu suara pada rekap agregat (upsert atomik); dipanggil saat surat suara dibuat. */
    public static function tambahHitungan(int $pemilihanId, int $kandidatId): void
    {
        $diubah = \App\Models\VoteTally::query()
            ->where(['election_id' => $pemilihanId, 'candidate_id' => $kandidatId])
            ->increment('jumlah');

        if ($diubah === 0) {
            \Illuminate\Support\Facades\DB::table('vote_tallies')->insertOrIgnore(['election_id' => $pemilihanId, 'candidate_id' => $kandidatId, 'jumlah' => 0]);
            \App\Models\VoteTally::query()->where(['election_id' => $pemilihanId, 'candidate_id' => $kandidatId])->increment('jumlah');
        }
    }

    /**
     * Hitung ulang rekap agregat dari surat suara anonim (GROUP BY di basis data).
     * Untuk verifikasi/pemulihan; mengembalikan kandidat yang hitungannya sempat berbeda.
     *
     * @return array<int, array{rekap: int, ballots: int}> candidate_id => selisih
     */
    public function hitungUlang(Election $pemilihan): array
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($pemilihan) {
            $sebenarnya = \App\Models\Ballot::query()->where('election_id', $pemilihan->id)
                ->selectRaw('candidate_id, count(*) as jumlah')->groupBy('candidate_id')->pluck('jumlah', 'candidate_id');
            $tercatat = \App\Models\VoteTally::query()->where('election_id', $pemilihan->id)->lockForUpdate()->pluck('jumlah', 'candidate_id');

            $beda = [];
            foreach ($pemilihan->candidates()->pluck('id') as $id) {
                $a = (int) ($sebenarnya[$id] ?? 0);
                $b = (int) ($tercatat[$id] ?? 0);
                if ($a !== $b) {
                    $beda[$id] = ['rekap' => $b, 'ballots' => $a];
                }
            }

            \App\Models\VoteTally::query()->where('election_id', $pemilihan->id)->delete();
            \Illuminate\Support\Facades\DB::table('vote_tallies')->insert($sebenarnya->map(fn ($n, $id) => ['election_id' => $pemilihan->id, 'candidate_id' => $id, 'jumlah' => $n])->values()->all());
            self::lupakanCache($pemilihan->id);

            return $beda;
        });
    }

    /** Buang cache rekap — dipanggil setiap ada suara baru. */
    public static function lupakanCache(int $pemilihanId): void
    {
        Cache::forget(self::kunciCache($pemilihanId));
    }

    /** Rekap lengkap dengan bentuk yang dipakai halaman Hasil (di-cache singkat). */
    public function rekap(?Election $pemilihan = null): ?array
    {
        $pemilihan ??= $this->pemilihan->pemilihanAktif();

        if (! $pemilihan) {
            return null;
        }

        return Cache::remember(self::kunciCache($pemilihan->id), self::CACHE_DETIK, fn () => $this->hitungRekap($pemilihan));
    }

    private function hitungRekap(Election $pemilihan): array
    {
        $kandidat = $this->perolehanSuara($pemilihan);

        return [
            'judul' => $pemilihan->judul,
            'status' => $this->pemilihan->statusEfektif($pemilihan)->value,
            'hasil_publik' => $pemilihan->hasil_publik,
            'mulai' => $pemilihan->mulai->toDateTimeString(),
            'total_pemilih' => $this->jumlahPemilih(),
            'suara_masuk' => array_sum(array_column($kandidat, 'suara')),
            'diperbarui' => now()->toDateTimeString(),
            'kandidat' => $kandidat,
        ];
    }

    /**
     * Pemenang pemilihan dari perolehan suara.
     *
     * @return array{status: 'menang'|'seri'|'kosong', kandidat: ?array, suara_masuk: int}
     */
    public function pemenang(Election $pemilihan): array
    {
        $urut = collect($this->perolehanSuara($pemilihan))->sortByDesc('suara')->values();
        $suaraMasuk = (int) $urut->sum('suara');
        $teratas = $urut->first();

        if (! $teratas || $teratas['suara'] === 0) {
            return ['status' => 'kosong', 'kandidat' => null, 'suara_masuk' => $suaraMasuk];
        }

        if (($urut[1]['suara'] ?? -1) === $teratas['suara']) {
            return ['status' => 'seri', 'kandidat' => null, 'suara_masuk' => $suaraMasuk];
        }

        return ['status' => 'menang', 'kandidat' => $teratas, 'suara_masuk' => $suaraMasuk];
    }

    /**
     * Ubah status publikasi hasil. Hanya bisa dipublikasikan setelah
     * pemilihan ditutup (status efektif "selesai").
     *
     * @throws ValidationException
     */
    public function aturPublikasi(Election $pemilihan, bool $publik, ?\App\Models\User $oleh = null): Election
    {
        if ($publik && $this->pemilihan->statusEfektif($pemilihan) !== StatusPemilihan::Selesai) {
            throw ValidationException::withMessages([
                'publikasi' => 'Hasil hanya bisa dipublikasikan setelah pemilihan ditutup.',
            ]);
        }

        $pemilihan->update([
            'hasil_publik' => $publik,
            // Jejak siapa & kapan mempublikasikan; dikosongkan saat ditarik.
            'hasil_dipublikasikan_pada' => $publik ? now() : null,
            'hasil_dipublikasikan_oleh' => $publik ? ($oleh ?? auth()->user())?->id : null,
        ]);
        self::lupakanCache($pemilihan->id);

        return $pemilihan;
    }
}
