<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Surat suara anonim (tabel ballots): pilihan tanpa identitas pemilih.
 * Tidak ada voter_id, waktu, maupun id berurutan; tidak bisa diubah setelah dicatat.
 */
class Ballot extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['election_id', 'candidate_id'];

    protected static function booted(): void
    {
        // UUID v4 acak (bukan berurutan waktu) agar tidak bisa dikorelasikan dengan urutan kehadiran.
        static::creating(fn (Ballot $b) => $b->id ??= (string) Str::uuid());
        static::updating(fn () => throw new \LogicException('Surat suara tidak bisa diubah.'));
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
