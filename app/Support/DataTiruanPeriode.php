<?php

namespace App\Support;

/**
 * Data tiruan Kelola Periode OSIM selama fase frontend.
 * Disimpan di sesi agar aksi di halaman bisa dicoba; diganti tabel osis_periods di layer backend.
 */
class DataTiruanPeriode
{
    private const KUNCI = 'tiruan.periode-osim';

    /** status => [label, warna, ikon, keterangan] */
    public const STATUS = [
        'aktif' => ['Aktif', 'success', 'heroicon-m-check-circle', 'Periode berjalan; data baru masuk ke sini.'],
        'disiapkan' => ['Disiapkan', 'gray', 'heroicon-m-clock', 'Belum aktif; data masih kosong.'],
        'arsip' => ['Arsip', 'warning', 'heroicon-m-archive-box', 'Hanya dibaca; tidak tercampur periode berjalan.'],
    ];

    /** Urutan tampil: aktif, disiapkan, lalu arsip terbaru. */
    public const URUTAN = ['aktif' => 0, 'disiapkan' => 1, 'arsip' => 2];

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

    /** Akun pengurus beserta penanda masih menjabat (users.is_aktif) di periode aktif. */
    public static function pengurus(): array
    {
        return [
            ['nama' => 'Ahmad Fauzan', 'email' => 'ahmad.fauzan@osim.test', 'is_aktif' => true],
            ['nama' => 'Siti Nurhaliza', 'email' => 'siti.nurhaliza@osim.test', 'is_aktif' => true],
            ['nama' => 'Rania Putri', 'email' => 'rania.putri@osim.test', 'is_aktif' => true],
            ['nama' => 'Yusuf Maulana', 'email' => 'yusuf.maulana@osim.test', 'is_aktif' => true],
            ['nama' => 'Dimas Arya', 'email' => 'dimas.arya@osim.test', 'is_aktif' => false],
            ['nama' => 'Farah Nabila', 'email' => 'farah.nabila@osim.test', 'is_aktif' => false],
        ];
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
