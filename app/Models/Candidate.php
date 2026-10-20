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

    /** Ketua + wakil? (pemilihan berformat ketua saja tidak punya wakil). */
    public function berpasangan(): bool
    {
        return filled($this->nama_wakil);
    }

    /** "Ketua & Wakil" atau "Ketua". */
    public function namaTampil(): string
    {
        return $this->berpasangan() ? $this->nama_ketua.' & '.$this->nama_wakil : $this->nama_ketua;
    }

    /** Alamat foto untuk ditampilkan: unggahan panel (disk public) atau URL lengkap. */
    public function fotoPublik(): ?string
    {
        if (blank($this->foto_url) || str_starts_with($this->foto_url, 'http') || str_starts_with($this->foto_url, '/')) {
            return $this->foto_url ?: null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->foto_url);
    }

    public function akun(): HasMany
    {
        return $this->hasMany(User::class, 'candidate_id');
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    /** Surat suara anonim untuk kandidat ini (tanpa identitas pemilih). */
    public function votes(): HasMany
    {
        return $this->hasMany(Ballot::class);
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
