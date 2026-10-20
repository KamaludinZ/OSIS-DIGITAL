<?php

namespace App\Support;

/**
 * Data tiruan Kelola Periode OSIM selama fase frontend.
 * Disimpan di sesi agar aksi di halaman bisa dicoba; diganti tabel osis_periods di layer backend.
 */
class DataTiruanPeriode
{
    private const KUNCI = 'tiruan.periode-osim';

    public const STATUS = [
        'aktif' => ['Aktif', 'success'],
        'disiapkan' => ['Disiapkan', 'gray'],
        'arsip' => ['Arsip', 'warning'],
    ];

    /** @return array<int, array<string, mixed>> */
    public static function semua(): array
    {
        return session(self::KUNCI, self::awal());
    }

    /** @return array<string, mixed>|null */
    public static function aktif(): ?array
    {
        return collect(self::semua())->firstWhere('status', 'aktif');
    }

    /** @param  array<int, array<string, mixed>>  $data */
    public static function simpan(array $data): void
    {
        session([self::KUNCI => $data]);
    }

    public static function setelUlang(): void
    {
        session()->forget(self::KUNCI);
    }

    /** @return array<int, array<string, mixed>> */
    private static function awal(): array
    {
        return [
            3 => ['id' => 3, 'nama' => '2026/2027', 'mulai' => '2026-07-13', 'selesai' => '2027-06-25', 'status' => 'aktif', 'pengurus' => 26, 'kandidat' => 3, 'saran' => 14],
            2 => ['id' => 2, 'nama' => '2025/2026', 'mulai' => '2025-07-14', 'selesai' => '2026-06-26', 'status' => 'arsip', 'pengurus' => 24, 'kandidat' => 2, 'saran' => 41],
            1 => ['id' => 1, 'nama' => '2024/2025', 'mulai' => '2024-07-15', 'selesai' => '2025-06-20', 'status' => 'arsip', 'pengurus' => 22, 'kandidat' => 3, 'saran' => 0],
        ];
    }
}
