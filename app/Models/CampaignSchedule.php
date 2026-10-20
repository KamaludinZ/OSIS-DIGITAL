<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignSchedule extends Model
{
    // Periode dari kandidat lebih dulu; IkutPeriode menambah global scope (mode longgar) & penjaga arsip.
    use \App\Models\Concerns\PeriodeDariKandidat, \App\Models\Concerns\IkutPeriode;

    /** Data lama tanpa periode tetap terbaca; tanpa periode aktif tidak difilter. */
    public function periodeLonggar(): bool
    {
        return true;
    }

    /** @use HasFactory<\Database\Factories\CampaignScheduleFactory> */
    use HasFactory;

    protected $fillable = ['candidate_id', 'tanggal', 'waktu', 'tempat'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
