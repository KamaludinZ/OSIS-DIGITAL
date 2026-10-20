<?php

namespace App\Services;

use App\Enums\StatusPeriode;
use App\Exceptions\PeriodeDitolak;
use App\Models\OsisPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Siklus periode kepengurusan OSIM: buat, ubah, hapus, arsipkan. */
class LayananPeriode
{
    /** Periode baru selalu mulai sebagai "disiapkan" dengan data kosong. */
    public function buat(array $data): OsisPeriod
    {
        $this->validasi($data);

        return OsisPeriod::create([...$this->isian($data), 'status' => StatusPeriode::Disiapkan]);
    }

    /** @throws PeriodeDitolak */
    public function ubah(OsisPeriod $periode, array $data): OsisPeriod
    {
        if ($periode->adalahArsip()) {
            throw new PeriodeDitolak('Periode arsip hanya bisa dilihat.');
        }

        $this->validasi($data, $periode);
        $periode->update($this->isian($data));

        return $periode;
    }

    /** Hanya periode yang belum pernah aktif dan belum berisi data yang boleh dihapus. @throws PeriodeDitolak */
    public function hapus(OsisPeriod $periode): void
    {
        if ($periode->status !== StatusPeriode::Disiapkan || $periode->punyaData()) {
            throw new PeriodeDitolak('Periode yang pernah aktif atau sudah berisi data tidak bisa dihapus; arsipkan saja.');
        }

        $periode->delete();
    }

    /**
     * Jadikan satu-satunya periode aktif: periode aktif sebelumnya otomatis diarsipkan
     * dalam transaksi yang sama. Arsip tidak bisa diaktifkan kembali.
     *
     * @return OsisPeriod|null periode lama yang diarsipkan
     *
     * @throws PeriodeDitolak
     */
    public function aktifkan(OsisPeriod $periode): ?OsisPeriod
    {
        return DB::transaction(function () use ($periode) {
            $periode = OsisPeriod::query()->lockForUpdate()->findOrFail($periode->id);

            if ($periode->adalahAktif()) {
                throw new PeriodeDitolak('Periode '.$periode->nama.' sudah aktif.');
            }
            if ($periode->adalahArsip()) {
                throw new PeriodeDitolak('Periode arsip tidak bisa diaktifkan kembali.');
            }

            $lama = OsisPeriod::query()->where('status', StatusPeriode::Aktif)->lockForUpdate()->first();
            // Arsipkan dulu yang lama agar indeks "satu aktif" tidak pernah dilanggar.
            $lama?->update(['status' => StatusPeriode::Arsip]);
            $periode->update(['status' => StatusPeriode::Aktif]);

            return $lama;
        });
    }

    /** @throws PeriodeDitolak */
    public function arsipkan(OsisPeriod $periode): void
    {
        if ($periode->adalahArsip()) {
            throw new PeriodeDitolak('Periode ini sudah diarsipkan.');
        }

        $periode->update(['status' => StatusPeriode::Arsip]);
    }

    /**
     * Aturan rentang periode, per isian: nama "2026/2027" (tahun kedua = tahun pertama + 1),
     * mulai jatuh di tahun pertama, selesai sesudah mulai dan paling lambat akhir tahun kedua,
     * serta tidak bertumpang tindih dengan periode lain.
     *
     * @return array<string, string> isian => pesan
     */
    public function periksaRentang(array $data, ?OsisPeriod $kecuali = null): array
    {
        $galat = [];

        if (! preg_match('/^(\d{4})\/(\d{4})$/', (string) ($data['nama'] ?? ''), $cocok)) {
            return ['nama' => 'Format periode: 2027/2028.'];
        }

        [$tahunAwal, $tahunAkhir] = [(int) $cocok[1], (int) $cocok[2]];
        if ($tahunAkhir !== $tahunAwal + 1) {
            $galat['nama'] = 'Tahun kedua harus satu tahun sesudah tahun pertama, contoh '.$tahunAwal.'/'.($tahunAwal + 1).'.';
        }

        if (blank($data['mulai'] ?? null) || blank($data['selesai'] ?? null)) {
            return $galat;
        }

        $mulai = Carbon::parse($data['mulai'])->startOfDay();
        $selesai = Carbon::parse($data['selesai'])->startOfDay();

        if ($mulai->year !== $tahunAwal) {
            $galat['mulai'] = 'Periode '.$data['nama'].' harus mulai di tahun '.$tahunAwal.'.';
        }

        if ($selesai->lessThanOrEqualTo($mulai)) {
            $galat['selesai'] = 'Tanggal selesai harus sesudah tanggal mulai.';
        } elseif ($selesai->year > $tahunAwal + 1) {
            $galat['selesai'] = 'Periode '.$data['nama'].' paling lambat selesai 31 Desember '.($tahunAwal + 1).'.';
        }

        if (! isset($galat['selesai'])) {
            $bentrok = OsisPeriod::query()
                ->when($kecuali, fn ($q) => $q->whereKeyNot($kecuali->id))
                ->whereDate('mulai', '<=', $selesai)
                ->whereDate('selesai', '>=', $mulai)
                ->orderBy('mulai')
                ->first();

            if ($bentrok) {
                $galat['mulai'] = 'Rentang tanggal bertumpang tindih dengan periode '.$bentrok->nama.' ('.$bentrok->rentang().').';
            }
        }

        return $galat;
    }

    /** @throws ValidationException */
    private function validasi(array $data, ?OsisPeriod $kecuali = null): void
    {
        if ($galat = $this->periksaRentang($data, $kecuali)) {
            throw ValidationException::withMessages($galat);
        }
    }

    /** @return array{nama: string, mulai: string, selesai: string} */
    private function isian(array $data): array
    {
        return [
            'nama' => $data['nama'],
            'mulai' => Carbon::parse($data['mulai'])->toDateString(),
            'selesai' => Carbon::parse($data['selesai'])->toDateString(),
        ];
    }
}
