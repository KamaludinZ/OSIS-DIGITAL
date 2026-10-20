<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Log satu kali impor data pemilih. */
class VoterImport extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['periode_id', 'user_id', 'jenis', 'nama_berkas', 'tambah', 'perbarui', 'sama', 'dilewati', 'galat'];

    protected function casts(): array
    {
        return ['galat' => 'array'];
    }

    public function pengimpor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(OsisPeriod::class, 'periode_id');
    }
}
