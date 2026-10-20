<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateQuestion extends Model
{
    use \App\Models\Concerns\PeriodeDariKandidat;

    /** @use HasFactory<\Database\Factories\CandidateQuestionFactory> */
    use HasFactory;

    protected $fillable = ['candidate_id', 'penanya_id', 'voter_id', 'pertanyaan', 'jawaban', 'dijawab_pada', 'ditampilkan'];

    protected function casts(): array
    {
        return ['dijawab_pada' => 'datetime', 'ditampilkan' => 'boolean'];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function voter(): BelongsTo
    {
        return $this->belongsTo(Voter::class);
    }

    public function penanya(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penanya_id');
    }

    /** Pertanyaan yang lolos moderasi. */
    public function scopeTampil(Builder $query): void
    {
        $query->where('ditampilkan', true);
    }
}
