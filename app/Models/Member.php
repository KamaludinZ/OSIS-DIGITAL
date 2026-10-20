<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Anggota pengurus OSIM. divisi_id null = pengurus inti. */
class Member extends Model
{
    /** @use HasFactory<\Database\Factories\MemberFactory> */
    use HasFactory;

    /**
     * Anggota dibatasi per periode lewat scope periode() (teks "2026/2027", dipakai luas);
     * periode_id ikut ditautkan agar arsip & penghapusan periode tetap konsisten.
     */
    protected static function booted(): void
    {
        static::creating(function (Member $m) {
            $m->periode ??= OsisPeriod::aktif()?->nama ?? static::periodeAktif();

            if (! $m->periode_id && ($id = OsisPeriod::query()->where('nama', $m->periode)->value('id'))) {
                $m->periode_id = $id;
            }
        });
    }

    public const JABATAN_INTI = ['Ketua OSIM', 'Wakil Ketua', 'Sekretaris', 'Bendahara'];

    protected $fillable = ['nama', 'kelas', 'divisi_id', 'jabatan', 'periode', 'voter_id', 'urutan'];

    public function divisi(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'divisi_id');
    }

    public function voter(): BelongsTo
    {
        return $this->belongsTo(Voter::class);
    }

    public function scopePeriode(Builder $query, ?string $periode = null): void
    {
        $query->where('periode', $periode ?? static::periodeAktif());
    }

    public function scopeInti(Builder $query): void
    {
        $query->whereNull('divisi_id');
    }

    /** Periode kepengurusan berjalan: Juli–Juni (tahun ajaran). */
    public static function periodeAktif(): string
    {
        $tahun = now()->month >= 7 ? now()->year : now()->year - 1;

        return $tahun.'/'.($tahun + 1);
    }
}
