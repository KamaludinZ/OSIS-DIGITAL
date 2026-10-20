<?php

namespace App\Enums;

enum StatusPeriode: string
{
    case Disiapkan = 'disiapkan';
    case Aktif = 'aktif';
    case Arsip = 'arsip';

    public function label(): string
    {
        return match ($this) {
            self::Disiapkan => 'Disiapkan',
            self::Aktif => 'Aktif',
            self::Arsip => 'Arsip',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Disiapkan => 'gray',
            self::Aktif => 'success',
            self::Arsip => 'warning',
        };
    }

    public function ikon(): string
    {
        return match ($this) {
            self::Disiapkan => 'heroicon-m-clock',
            self::Aktif => 'heroicon-m-check-circle',
            self::Arsip => 'heroicon-m-archive-box',
        };
    }

    public function keterangan(): string
    {
        return match ($this) {
            self::Disiapkan => 'Belum aktif; data masih kosong.',
            self::Aktif => 'Periode berjalan; data baru masuk ke sini.',
            self::Arsip => 'Hanya dibaca; tidak tercampur periode berjalan.',
        };
    }
}
