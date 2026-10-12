<?php

namespace App\Services;

use App\Models\CampaignMaterial;
use App\Models\CampaignSchedule;
use App\Models\CandidateQuestion;
use App\Models\User;
use App\Models\Voter;
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
                'url_konten' => $m->urlPublik(),
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
            ->with(['penanya:id,name,kelas', 'voter:id,jenis,nama,kelas,jabatan'])
            ->latest()
            ->latest('id')
            ->get()
            ->map(fn (CandidateQuestion $q) => $this->tanyaKeArray($q))
            ->all();
    }

    /**
     * Simpan pertanyaan untuk paslon. Penanya adalah pemilih terverifikasi
     * (siswa/GTK lewat NISN/NIK + tanggal lahir) atau akun pengurus/panitia.
     * Aman ditekan berulang:
     * - kunci per penanya mencegah dua permintaan bersamaan tersimpan;
     * - pertanyaan identik dalam 2 menit dikembalikan, tidak dibuat ulang;
     * - maksimal 5 pertanyaan baru per 10 menit per penanya (anti-spam).
     *
     * @throws ValidationException
     */
    public function kirimPertanyaan(Voter|User $penanya, Candidate $calon, string $pertanyaan): array
    {
        $pertanyaan = Str::squish($pertanyaan);
        $kolom = $penanya instanceof Voter ? 'voter_id' : 'penanya_id';
        $identitas = $kolom.':'.$penanya->id;

        $hasil = Cache::lock('kirim-pertanyaan:'.$identitas, 10)->block(5, function () use ($penanya, $kolom, $identitas, $calon, $pertanyaan) {
            $duplikat = $calon->questions()
                ->where($kolom, $penanya->id)
                ->where('pertanyaan', $pertanyaan)
                ->where('created_at', '>=', now()->subMinutes(2))
                ->first();

            if ($duplikat) {
                return $duplikat;
            }

            $kunci = 'tanya-kandidat:'.$identitas;

            if (RateLimiter::tooManyAttempts($kunci, 5)) {
                throw ValidationException::withMessages([
                    'pertanyaanBaru' => 'Terlalu banyak pertanyaan. Coba lagi dalam '.ceil(RateLimiter::availableIn($kunci) / 60).' menit.',
                ]);
            }

            RateLimiter::hit($kunci, 600);

            $baru = $calon->questions()->create([
                $kolom => $penanya->id,
                'pertanyaan' => $pertanyaan,
            ]);

            // Beri tahu akun kandidat yang ditanya (bila ada & mau menerima).
            $calon->akun()->where('aktif', true)->with('preferensiNotifikasi')->get()
                ->filter(fn (User $akun) => $akun->mauMenerima('pertanyaan'))
                ->whenNotEmpty(fn ($penerima) => \Filament\Notifications\Notification::make()
                    ->title('Pertanyaan baru untukmu')
                    ->body((string) Str::limit($pertanyaan, 140))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->actions([\Filament\Actions\Action::make('tanggapi')->label('Tanggapi')
                        ->url(route('kandidat.profil', ['id' => $calon->id, 'tab' => 'tanya-jawab']))])
                    ->sendToDatabase($penerima));

            return $baru;
        });

        return $this->tanyaKeArray($hasil->setRelation($penanya instanceof Voter ? 'voter' : 'penanya', $penanya));
    }

    /**
     * Tanggapan kandidat atas pertanyaan untuk dirinya. Menanggapi ulang
     * memperbarui jawaban; jawaban kosong menarik tanggapan.
     *
     * @throws ValidationException
     */
    public function jawabPertanyaan(User $kandidat, CandidateQuestion $tanya, ?string $jawaban): array
    {
        if (! $kandidat->can('jawab-pertanyaan', $tanya)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Hanya kandidat yang ditanya yang boleh menanggapi.');
        }

        $jawaban = trim((string) $jawaban);

        if (mb_strlen($jawaban) > 1000) {
            throw ValidationException::withMessages(['jawaban' => 'Tanggapan paling panjang 1000 karakter.']);
        }

        $tanya->update([
            'jawaban' => $jawaban !== '' ? $jawaban : null,
            'dijawab_pada' => $jawaban !== '' ? now() : null,
        ]);

        return $this->tanyaKeArray($tanya->load(['penanya:id,name,kelas', 'voter:id,jenis,nama,kelas,jabatan']));
    }

    private function tanyaKeArray(CandidateQuestion $q): array
    {
        $nama = match (true) {
            $q->voter_id && $q->voter => $q->voter->nama.' · '.($q->voter->jenis === \App\Enums\JenisPemilih::Siswa ? ($q->voter->kelas ?? 'Siswa') : ($q->voter->jabatan ?: 'GTK')),
            $q->penanya_id && $q->penanya => trim($q->penanya->name.($q->penanya->kelas ? ' · '.$q->penanya->kelas : '')),
            default => 'Penanya',
        };

        return [
            'id' => $q->id,
            'pertanyaan' => $q->pertanyaan,
            'penanya' => $nama,
            'jawaban' => $q->jawaban,
            'dijawab_pada' => $q->dijawab_pada?->toDateTimeString(),
            'created_at' => $q->created_at->toDateTimeString(),
        ];
    }
}
