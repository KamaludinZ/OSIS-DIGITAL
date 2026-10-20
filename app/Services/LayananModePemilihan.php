<?php

namespace App\Services;

use App\Exceptions\PeriodeDitolak;
use App\Models\OsisPeriod;
use App\Models\User;

/** Sakelar mode pemilihan milik periode aktif; menentukan menu & alamat publik yang terbuka. */
class LayananModePemilihan
{
    /** Tanpa periode aktif, situs tetap dalam mode pemilihan (perilaku sebelum ada sakelar). */
    public function aktif(): bool
    {
        return OsisPeriod::aktif()?->mode_pemilihan ?? true;
    }

    /** @return array{aktif: bool, periode: ?string, diubah_pada: ?\Illuminate\Support\Carbon, diubah_oleh: ?string} */
    public function status(): array
    {
        $periode = OsisPeriod::aktif()?->loadMissing('pengubahMode');

        return [
            'aktif' => $this->aktif(),
            'periode' => $periode?->nama,
            'diubah_pada' => $periode?->mode_diubah_pada,
            'diubah_oleh' => $periode?->pengubahMode?->name,
        ];
    }

    /** @throws PeriodeDitolak */
    public function ubah(bool $aktif, User $oleh): OsisPeriod
    {
        $periode = OsisPeriod::aktif() ?? throw new PeriodeDitolak('Belum ada periode aktif. Aktifkan periode OSIM dulu.');

        $periode->update(['mode_pemilihan' => $aktif, 'mode_diubah_pada' => now(), 'mode_diubah_oleh' => $oleh->id]);

        return $periode;
    }

    /** @throws PeriodeDitolak */
    public function balik(User $oleh): bool
    {
        return $this->ubah(! $this->aktif(), $oleh)->mode_pemilihan;
    }
}
