<?php

namespace App\Models;

use App\Enums\StatusPeriode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Periode kepengurusan OSIM. Data operasional ber-periode_id mengikuti periode aktif. */
class OsisPeriod extends Model
{
    /** @use HasFactory<\Database\Factories\OsisPeriodFactory> */
    use HasFactory;

    private const KUNCI_AKTIF = 'osim.periode-aktif';

    protected $fillable = ['nama', 'mulai', 'selesai', 'status'];

    protected $attributes = ['status' => 'disiapkan'];

    protected static function booted(): void
    {
        static::saved(fn () => static::lupakanAktif());
        static::deleted(fn () => static::lupakanAktif());
    }

    protected function casts(): array
    {
        return [
            'mulai' => 'date',
            'selesai' => 'date',
            'status' => StatusPeriode::class,
        ];
    }

    /** Periode berstatus aktif; diingat selama satu permintaan. */
    public static function aktif(): ?self
    {
        if (! app()->bound(self::KUNCI_AKTIF)) {
            app()->instance(self::KUNCI_AKTIF, ['periode' => static::query()->where('status', StatusPeriode::Aktif)->first()]);
        }

        return app(self::KUNCI_AKTIF)['periode'];
    }

    public static function lupakanAktif(): void
    {
        app()->forgetInstance(self::KUNCI_AKTIF);
    }

    public function scopeStatus(Builder $query, StatusPeriode $status): void
    {
        $query->where('status', $status);
    }

    public function adalahAktif(): bool
    {
        return $this->status === StatusPeriode::Aktif;
    }

    public function adalahArsip(): bool
    {
        return $this->status === StatusPeriode::Arsip;
    }

    /** Contoh: "13 Jul 2026 – 25 Jun 2027". */
    public function rentang(): string
    {
        return $this->mulai->translatedFormat('j M Y').' – '.$this->selesai->translatedFormat('j M Y');
    }
}
