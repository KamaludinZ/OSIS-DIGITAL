<?php

namespace App\Services;

use App\Models\CampaignMaterial;
use App\Models\CampaignSchedule;
use App\Models\CandidateQuestion;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\Candidate;

/**
 * Data kampanye pasangan calon (materi, jadwal, tanya jawab) untuk
 * antarmuka siswa. Mengembalikan array sederhana untuk state Livewire.
 */
class LayananKampanye
{
    /** Materi kampanye satu paslon, urut sesuai `urutan`. */
    public function materi(int $kandidatId): array
    {
        return CampaignMaterial::query()
            ->where('candidate_id', $kandidatId)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get()
            ->map(fn (CampaignMaterial $m) => [
                'id' => $m->id,
                'tipe' => $m->tipe->value,
                'url_konten' => $m->url_konten,
                'deskripsi' => $m->deskripsi,
            ])
            ->all();
    }

    /** Jadwal kampanye satu paslon, urut tanggal lalu jam. */
    public function jadwal(int $kandidatId): array
    {
        return CampaignSchedule::query()
            ->where('candidate_id', $kandidatId)
            ->orderBy('tanggal')
            ->orderBy('waktu')
            ->get()
            ->map(fn (CampaignSchedule $j) => [
                'id' => $j->id,
                'tanggal' => $j->tanggal->toDateString(),
                'waktu' => substr($j->waktu, 0, 5),
                'tempat' => $j->tempat,
            ])
            ->all();
    }

    /** Tanya jawab yang lolos moderasi, terbaru dulu. */
    public function tanyaJawab(int $kandidatId): array
    {
        return CandidateQuestion::query()
            ->where('candidate_id', $kandidatId)
            ->tampil()
            ->with('penanya:id,name,kelas')
            ->latest()
            ->latest('id')
            ->get()
            ->map(fn (CandidateQuestion $q) => $this->tanyaKeArray($q))
            ->all();
    }

    /**
     * Simpan pertanyaan siswa untuk paslon. Aman ditekan berulang:
     * - kunci per pengguna mencegah dua permintaan bersamaan tersimpan;
     * - pertanyaan identik dalam 2 menit dikembalikan, tidak dibuat ulang;
     * - maksimal 5 pertanyaan baru per 10 menit per akun (anti-spam).
     *
     * @throws ValidationException
     */
    public function kirimPertanyaan(User $penanya, Candidate $calon, string $pertanyaan): array
    {
        $pertanyaan = Str::squish($pertanyaan);

        $hasil = Cache::lock('kirim-pertanyaan:'.$penanya->id, 10)->block(5, function () use ($penanya, $calon, $pertanyaan) {
            $duplikat = $calon->questions()
                ->where('penanya_id', $penanya->id)
                ->where('pertanyaan', $pertanyaan)
                ->where('created_at', '>=', now()->subMinutes(2))
                ->first();

            if ($duplikat) {
                return $duplikat;
            }

            $kunci = 'tanya-kandidat:'.$penanya->id;

            if (RateLimiter::tooManyAttempts($kunci, 5)) {
                throw ValidationException::withMessages([
                    'pertanyaanBaru' => 'Terlalu banyak pertanyaan. Coba lagi dalam '.ceil(RateLimiter::availableIn($kunci) / 60).' menit.',
                ]);
            }

            RateLimiter::hit($kunci, 600);

            return $calon->questions()->create([
                'penanya_id' => $penanya->id,
                'pertanyaan' => $pertanyaan,
            ]);
        });

        return $this->tanyaKeArray($hasil->setRelation('penanya', $penanya));
    }

    private function tanyaKeArray(CandidateQuestion $q): array
    {
        $penanya = $q->penanya;

        return [
            'id' => $q->id,
            'pertanyaan' => $q->pertanyaan,
            'penanya' => $penanya ? trim($penanya->name.($penanya->kelas ? ' · '.$penanya->kelas : '')) : 'Siswa',
            'jawaban' => $q->jawaban,
            'created_at' => $q->created_at->toDateTimeString(),
        ];
    }
}
