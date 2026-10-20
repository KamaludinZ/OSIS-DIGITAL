<?php

namespace App\Services;

use App\Enums\StatusPeriode;
use App\Exceptions\PeriodeDitolak;
use App\Models\OsisPeriod;
use Illuminate\Support\Carbon;

/** Siklus periode kepengurusan OSIM: buat, ubah, hapus, arsipkan. */
class LayananPeriode
{
    /** Periode baru selalu mulai sebagai "disiapkan" dengan data kosong. */
    public function buat(array $data): OsisPeriod
    {
        return OsisPeriod::create([...$this->isian($data), 'status' => StatusPeriode::Disiapkan]);
    }

    /** @throws PeriodeDitolak */
    public function ubah(OsisPeriod $periode, array $data): OsisPeriod
    {
        if ($periode->adalahArsip()) {
            throw new PeriodeDitolak('Periode arsip hanya bisa dilihat.');
        }

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

    /** @throws PeriodeDitolak */
    public function arsipkan(OsisPeriod $periode): void
    {
        if ($periode->adalahArsip()) {
            throw new PeriodeDitolak('Periode ini sudah diarsipkan.');
        }

        $periode->update(['status' => StatusPeriode::Arsip]);
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
