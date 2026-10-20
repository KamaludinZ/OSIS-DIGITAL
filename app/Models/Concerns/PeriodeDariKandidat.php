<?php

namespace App\Models\Concerns;

use App\Models\Candidate;

/**
 * Data kampanye (materi, jadwal, tanya jawab) tercatat di periode kandidatnya.
 * Dipakai bersama IkutPeriode (yang menyediakan relasi periode() & global scope).
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait PeriodeDariKandidat
{
    public static function bootPeriodeDariKandidat(): void
    {
        static::creating(function ($model) {
            $model->periode_id ??= Candidate::query()->whereKey($model->candidate_id)->value('periode_id');
        });
    }
}
