<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan kehadiran pemilih: siapa sudah memilih pada pemilihan mana + kode bukti.
 * TIDAK memuat pilihan; pilihan tersimpan terpisah dan anonim di Ballot.
 */
class Vote extends Model
{
    // Suara tidak pernah diubah setelah dikirim, jadi hanya created_at.
    const UPDATED_AT = null;

    protected $fillable = [
        'election_id',
        'voter_id',
        'candidate_id', // hanya diterima untuk dipindah ke Ballot, tidak pernah disimpan di votes
        'kode_bukti',
    ];

    protected static function booted(): void
    {
        // Penjaga anonimitas: bila pilihan ikut diberikan, catat sebagai surat suara anonim
        // dan buang dari baris kehadiran agar pemilih tidak bisa dikaitkan dengan pilihannya.
        static::creating(function (Vote $vote) {
            if ($kandidat = $vote->getAttribute('candidate_id')) {
                Ballot::create(['election_id' => $vote->election_id, 'candidate_id' => $kandidat]);
            }
            unset($vote->candidate_id);
        });

        // Suara tidak pernah diubah setelah dikirim (juga dijaga trigger basis data).
        static::updating(fn () => throw new \LogicException('Suara yang sudah tercatat tidak bisa diubah.'));
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function voter(): BelongsTo
    {
        return $this->belongsTo(Voter::class);
    }
}
