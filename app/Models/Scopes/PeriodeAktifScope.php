<?php

namespace App\Models\Scopes;

use App\Models\OsisPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Batasi data ke periode aktif. Tanpa periode aktif, tidak ada data yang tampil (tidak bocor antarperiode).
 * Model "longgar" (periodeLonggar() = true, mis. pemilih): tanpa periode aktif tidak difilter, dan data
 * lama yang belum punya periode tetap ikut periode aktif.
 */
class PeriodeAktifScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $aktif = OsisPeriod::aktif();
        $longgar = method_exists($model, 'periodeLonggar') && $model->periodeLonggar();
        $kolom = $model->qualifyColumn('periode_id');

        match (true) {
            $longgar && ! $aktif => null,
            $longgar => $builder->where(fn ($q) => $q->where($kolom, $aktif->id)->orWhereNull($kolom)),
            (bool) $aktif => $builder->where($kolom, $aktif->id),
            default => $builder->whereRaw('1 = 0'),
        };
    }
}
