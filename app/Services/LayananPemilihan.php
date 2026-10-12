<?php

namespace App\Services;

use App\Exceptions\SuaraDitolak;
use App\Enums\StatusPemilihan;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Voter;
use App\Models\Vote;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sumber data bilik suara untuk antarmuka siswa (Livewire).
 * Mengembalikan array sederhana agar aman disimpan sebagai state komponen.
 */
class LayananPemilihan
{
    public function pemilihanAktif(): ?Election
    {
        return Election::aktif();
    }

    /** Data periode pemilihan + status efektif (memperhitungkan jam buka/tutup). */
    public function ringkasanPemilihan(?Election $pemilihan = null): ?array
    {
        $pemilihan ??= $this->pemilihanAktif();

        if (! $pemilihan) {
            return null;
        }

        return [
            'id' => $pemilihan->id,
            'judul' => $pemilihan->judul,
            'format_kandidat' => $pemilihan->format_kandidat->value,
            'status' => $this->statusEfektif($pemilihan)->value,
            'mulai' => $pemilihan->mulai->toDateTimeString(),
            'selesai' => $pemilihan->selesai->toDateTimeString(),
            'hasil_publik' => $pemilihan->hasil_publik,
        ];
    }

    /** Daftar pasangan calon pada pemilihan, urut nomor. */
    public function daftarKandidat(?Election $pemilihan = null): array
    {
        $pemilihan ??= $this->pemilihanAktif();

        if (! $pemilihan) {
            return [];
        }

        return $pemilihan->candidates
            ->map(fn (Candidate $calon) => $this->kandidatKeArray($calon))
            ->all();
    }

    /**
     * Detail satu pasangan calon pada periode aktif. Paslon dari periode
     * lain (lama/draf panitia) tidak bisa dibuka siswa.
     */
    public function cariKandidat(int $id, ?Election $pemilihan = null): ?array
    {
        $pemilihan ??= $this->pemilihanAktif();
        $calon = $pemilihan?->candidates()->whereKey($id)->first();

        return $calon ? $this->kandidatKeArray($calon) : null;
    }

    /**
     * Status "berjalan" di database baru benar-benar dibuka saat jam
     * sekarang ada di antara mulai dan selesai.
     */
    public function statusEfektif(Election $pemilihan): StatusPemilihan
    {
        if ($pemilihan->status !== StatusPemilihan::Berjalan) {
            return $pemilihan->status;
        }

        return match (true) {
            now()->lt($pemilihan->mulai) => StatusPemilihan::Draft,
            now()->gt($pemilihan->selesai) => StatusPemilihan::Selesai,
            default => StatusPemilihan::Berjalan,
        };
    }

    /**
     * Verifikasi kelayakan pemilih. Urutan cek: pemilihan dibuka → kode pilih
     * sudah dimasukkan → data pemilih terverifikasi → belum pernah memilih.
     *
     * @return array{boleh: bool, alasan: ?string, pesan: ?string}
     */
    public function kelayakan(?Voter $pemilih, ?Election $pemilihan = null): array
    {
        $pemilihan ??= $this->pemilihanAktif();

        $tolak = fn (string $alasan, string $pesan) => ['boleh' => false, 'alasan' => $alasan, 'pesan' => $pesan];

        if (! $pemilihan || $this->statusEfektif($pemilihan) !== StatusPemilihan::Berjalan) {
            return $tolak('pemilihan_tertutup', 'Bilik suara sedang tidak dibuka.');
        }

        if (! $pemilih) {
            return $tolak('belum_kode', 'Masukkan kode pilih 6 digit dari panitia untuk menggunakan hak pilih.');
        }

        if ($pemilih->verifikasi !== 'terverifikasi') {
            return $tolak('bukan_pemilih', $pemilih->verifikasi === 'menunggu'
                ? 'Data pemilihmu masih menunggu verifikasi panitia.'
                : 'Data pemilihmu tidak lolos verifikasi. Hubungi panitia pemilihan.');
        }

        if ($pemilih->sudahMemilih($pemilihan)) {
            return $tolak('sudah_memilih', 'Anda sudah menggunakan hak pilih pada pemilihan ini.');
        }

        return ['boleh' => true, 'alasan' => null, 'pesan' => null];
    }

    /**
     * Catat satu suara. Validasi berlapis: kelayakan dicek di server, lalu
     * unique (election_id, user_id) di database menjadi penjaga terakhir
     * bila ada dua permintaan bersamaan.
     *
     * @throws SuaraDitolak
     */
    public function kirimSuara(Voter $pemilih, Election $pemilihan, int $kandidatId): Vote
    {
        $kelayakan = $this->kelayakan($pemilih, $pemilihan);

        if (! $kelayakan['boleh']) {
            throw new SuaraDitolak($kelayakan['pesan'], $kelayakan['alasan']);
        }

        $calon = $pemilihan->candidates()->whereKey($kandidatId)->first();

        if (! $calon) {
            throw new SuaraDitolak(ucfirst(\App\Support\Istilah::calon()).' tidak ditemukan pada pemilihan ini.', 'kandidat_tidak_valid');
        }

        try {
            $suara = DB::transaction(fn () => Vote::create([
                'election_id' => $pemilihan->id,
                'voter_id' => $pemilih->id,
                'candidate_id' => $calon->id,
                'kode_bukti' => $this->buatKodeBukti(),
            ]));

            // Rekap di halaman Hasil langsung ikut berubah.
            LayananHasil::lupakanCache($pemilihan->id);

            return $suara;
        } catch (UniqueConstraintViolationException) {
            throw new SuaraDitolak('Anda sudah menggunakan hak pilih pada pemilihan ini.', 'sudah_memilih');
        }
    }

    /** Apakah pengguna sudah memilih di pemilihan aktif. */
    public function sudahMemilih(?Voter $pemilih, ?Election $pemilihan = null): bool
    {
        $pemilihan ??= $this->pemilihanAktif();

        return $pemilih && $pemilihan && $pemilih->sudahMemilih($pemilihan);
    }

    /** Bukti suara milik pengguna (tanpa kandidat yang dipilih). */
    public function buktiSuara(Voter $pemilih, ?Election $pemilihan = null): ?array
    {
        $pemilihan ??= $this->pemilihanAktif();
        $suara = $pemilihan ? $pemilih->votes()->where('election_id', $pemilihan->id)->first() : null;

        return $suara ? [
            'kode' => $suara->kode_bukti,
            'pemilihan' => $pemilihan->judul,
            'waktu' => $suara->created_at->toDateTimeString(),
        ] : null;
    }

    private function buatKodeBukti(): string
    {
        do {
            $kode = 'OSIM-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4));
        } while (Vote::where('kode_bukti', $kode)->exists());

        return $kode;
    }

    private function kandidatKeArray(Candidate $calon): array
    {
        return [
            'id' => $calon->id,
            'election_id' => $calon->election_id,
            'nomor_urut' => $calon->nomor_urut,
            'nama_ketua' => $calon->nama_ketua,
            'kelas_ketua' => $calon->kelas_ketua,
            'nama_wakil' => $calon->nama_wakil,
            'kelas_wakil' => $calon->kelas_wakil,
            'berpasangan' => $calon->berpasangan(),
            'nama_tampil' => $calon->namaTampil(),
            'slogan' => $calon->slogan,
            'latar_belakang' => $calon->latar_belakang,
            'visi' => $calon->visi,
            'misi' => $calon->misi ?? [],
            'foto_url' => $calon->fotoPublik(),
        ];
    }
}
