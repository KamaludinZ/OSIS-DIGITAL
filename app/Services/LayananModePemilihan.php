<?php

namespace App\Services;

use App\Exceptions\PeriodeDitolak;
use App\Models\OsisPeriod;
use App\Models\User;
use App\Support\MenuPublik;

/** Sakelar mode pemilihan milik periode aktif; menentukan menu & alamat publik yang terbuka. */
class LayananModePemilihan
{
    /**
     * Penentu mode pemilihan yang berlaku untuk situs:
     * - ada periode aktif: mengikuti sakelar periode itu;
     * - tanpa periode aktif: tetap mode pemilihan (perilaku sebelum ada sakelar).
     */
    public function aktif(): bool
    {
        return OsisPeriod::aktif()?->mode_pemilihan ?? true;
    }

    /**
     * Peringatan bila sakelar tidak selaras dengan jadwal pemilihan, mis. bilik suara
     * sedang terjadwal dibuka tetapi mode pemilihan dimatikan (pemilih tidak bisa masuk).
     */
    public function peringatan(): ?string
    {
        $pemilihan = app(LayananPemilihan::class)->pemilihanAktif();

        if (! $this->aktif() && $pemilihan?->sedangDibuka()) {
            return 'Pemilihan "'.$pemilihan->judul.'" sedang dibuka, tetapi mode pemilihan nonaktif: pemilih tidak bisa masuk bilik suara.';
        }

        return null;
    }

    /**
     * Status sakelar untuk Ruang OSIM: mode, periode, jejak perubahan, dan menu yang sedang tampil.
     *
     * @return array{aktif: bool, periode: ?string, diubah_pada: ?\Illuminate\Support\Carbon, diubah_oleh: ?string, bisa_diubah: bool, peringatan: ?string, menu: list<array>}
     */
    public function status(): array
    {
        $periode = OsisPeriod::aktif()?->loadMissing('pengubahMode');

        return [
            'aktif' => $this->aktif(),
            'periode' => $periode?->nama,
            'diubah_pada' => $periode?->mode_diubah_pada,
            'diubah_oleh' => $periode?->pengubahMode?->name,
            'bisa_diubah' => $periode !== null,
            'peringatan' => $this->peringatan(),
            'menu' => $this->menu(),
        ];
    }

    /** Menu publik untuk mode tertentu (bawaan: mode saat ini). */
    public function menu(?bool $modePemilihan = null): array
    {
        return MenuPublik::untuk($modePemilihan ?? $this->aktif());
    }

    /**
     * Pratinjau menu publik kedua mode, ditandai mana yang sedang tampil.
     *
     * @return array{aktif: array{menu: list<array>, sedang_tampil: bool}, nonaktif: array{menu: list<array>, sedang_tampil: bool}}
     */
    public function pratinjau(): array
    {
        $aktif = $this->aktif();

        return [
            'aktif' => ['menu' => $this->menu(true), 'sedang_tampil' => $aktif],
            'nonaktif' => ['menu' => $this->menu(false), 'sedang_tampil' => ! $aktif],
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
