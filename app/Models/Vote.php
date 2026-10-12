<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    // Suara tidak pernah diubah setelah dikirim, jadi hanya created_at.
    const UPDATED_AT = null;

    protected $fillable = [
        'election_id',
        'voter_id',
        'candidate_id',
        'kode_bukti',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function voter(): BelongsTo
    {
        return $this->belongsTo(Voter::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
