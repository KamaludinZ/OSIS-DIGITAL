<?php

namespace App\Models\Concerns;

use App\Models\Candidate;
use App\Models\OsisPeriod;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Data kampanye (materi, jadwal, tanya jawab) tercatat di periode kandidatnya.
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

    public function periode(): BelongsTo
    {
        return $this->belongsTo(OsisPeriod::class, 'periode_id');
    }
}
