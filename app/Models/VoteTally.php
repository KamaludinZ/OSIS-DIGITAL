<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Rekap agregat suara per kandidat (tabel vote_tallies); dihitung ulang dari ballots bila perlu. */
class VoteTally extends Model
{
    protected $table = 'vote_tallies';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = ['election_id', 'candidate_id', 'jumlah'];

    protected function casts(): array
    {
        return ['jumlah' => 'integer'];
    }
}
