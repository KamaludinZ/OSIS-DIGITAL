<?php

namespace App\Models\Scopes;

use App\Models\OsisPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/** Batasi data ke periode aktif. Tanpa periode aktif, tidak ada data yang tampil (tidak bocor antarperiode). */
class PeriodeAktifScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $aktif = OsisPeriod::aktif();

        $aktif
            ? $builder->where($model->qualifyColumn('periode_id'), $aktif->id)
            : $builder->whereRaw('1 = 0');
    }
}
