<?php

namespace App\Models\Concerns;

use App\Models\OsisPeriod;
use App\Models\Scopes\PeriodeAktifScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Data operasional per periode: kueri otomatis dibatasi ke periode aktif
 * dan baris baru otomatis masuk periode aktif.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait IkutPeriode
{
    public static function bootIkutPeriode(): void
    {
        static::addGlobalScope(new PeriodeAktifScope);

        static::creating(function ($model) {
            $model->periode_id ??= OsisPeriod::aktif()?->id;
        });
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(OsisPeriod::class, 'periode_id');
    }

    /** Lepas batas periode aktif, mis. untuk membaca arsip. */
    public function scopeSemuaPeriode(Builder $query): void
    {
        $query->withoutGlobalScope(PeriodeAktifScope::class);
    }

    public function scopeDiPeriode(Builder $query, OsisPeriod|int $periode): void
    {
        $query->withoutGlobalScope(PeriodeAktifScope::class)
            ->where($this->qualifyColumn('periode_id'), $periode instanceof OsisPeriod ? $periode->id : $periode);
    }
}
