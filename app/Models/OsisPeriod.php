<?php

namespace App\Models;

use App\Enums\StatusPeriode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Periode kepengurusan OSIM. Data operasional ber-periode_id mengikuti periode aktif. */
class OsisPeriod extends Model
{
    /** @use HasFactory<\Database\Factories\OsisPeriodFactory> */
    use HasFactory;

    private const KUNCI_AKTIF = 'osim.periode-aktif';

    protected $fillable = ['nama', 'mulai', 'selesai', 'status', 'mode_pemilihan', 'mode_diubah_pada', 'mode_diubah_oleh'];

    protected $attributes = ['status' => 'disiapkan', 'mode_pemilihan' => false];

    protected static function booted(): void
    {
        static::saved(fn () => static::lupakanAktif());
        // Mode/periode aktif berganti: umpan pemberitahuan publik disusun ulang.
        static::saved(function (OsisPeriod $periode) {
            if ($periode->wasChanged(['mode_pemilihan', 'status']) || $periode->wasRecentlyCreated) {
                \App\Services\LayananNotifikasi::lupakan();
            }
        });
        static::deleted(fn () => static::lupakanAktif());
    }

    protected function casts(): array
    {
        return [
            'mulai' => 'date',
            'selesai' => 'date',
            'status' => StatusPeriode::class,
            'mode_pemilihan' => 'boolean',
            'mode_diubah_pada' => 'datetime',
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

    /** Akun yang terakhir mengubah sakelar mode pemilihan. */
    public function pengubahMode(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'mode_diubah_oleh');
    }

    // Relasi isi periode melepas batas periode aktif agar arsip tetap terbaca.
    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'periode_id')->withoutGlobalScope(Scopes\PeriodeAktifScope::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class, 'periode_id')->withoutGlobalScope(Scopes\PeriodeAktifScope::class);
    }

    public function elections(): HasMany
    {
        return $this->hasMany(Election::class, 'periode_id')->withoutGlobalScope(Scopes\PeriodeAktifScope::class);
    }

    public function struktur(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(OrganizationStructure::class, 'periode_id')->withoutGlobalScope(Scopes\PeriodeAktifScope::class);
    }

    /** Sudah ada data operasional yang menempel ke periode ini. */
    public function punyaData(): bool
    {
        return collect(['members', 'work_programs', 'events', 'social_posts', 'elections', 'candidates', 'organization_structures'])
            ->contains(fn (string $tabel) => \Illuminate\Support\Facades\DB::table($tabel)->where('periode_id', $this->id)->exists());
    }

    public function scopeStatus(Builder $query, StatusPeriode $status): void
    {
        $query->where('status', $status);
    }

    /** Aktif dulu, lalu disiapkan, lalu arsip; dalam kelompok, yang terbaru di atas. */
    public function scopeUrutTampil(Builder $query): void
    {
        $query->orderByRaw("case status when 'aktif' then 0 when 'disiapkan' then 1 else 2 end")->orderByDesc('nama');
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
