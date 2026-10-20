<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignSchedule extends Model
{
    use \App\Models\Concerns\PeriodeDariKandidat;

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
