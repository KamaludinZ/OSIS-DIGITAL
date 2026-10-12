<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    /** @use HasFactory<\Database\Factories\CandidateFactory> */
    use HasFactory;

    protected $fillable = [
        'election_id',
        'nomor_urut',
        'nama_ketua',
        'kelas_ketua',
        'nama_wakil',
        'kelas_wakil',
        'slogan',
        'latar_belakang',
        'visi',
        'misi',
        'foto_url',
    ];

    protected function casts(): array
    {
        return [
            'nomor_urut' => 'integer',
            'misi' => 'array',
        ];
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(CampaignMaterial::class)->orderBy('urutan')->orderBy('id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(CampaignSchedule::class)->orderBy('tanggal')->orderBy('waktu');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(CandidateQuestion::class)->latest();
    }
}
