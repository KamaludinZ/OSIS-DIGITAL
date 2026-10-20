<?php

namespace App\Models;

use App\Models\Concerns\IkutPeriode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Saran dari Kotak Saran publik; otomatis masuk & dibatasi ke periode aktif. */
class Suggestion extends Model
{
    /** @use HasFactory<\Database\Factories\SuggestionFactory> */
    use HasFactory, IkutPeriode;

    protected $fillable = ['isi', 'nama', 'kelas'];

    protected function casts(): array
    {
        return ['ditanggapi_pada' => 'datetime'];
    }

    public function penanggap(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditanggapi_oleh');
    }

    public function anonim(): bool
    {
        return blank($this->nama);
    }

    /** "Nama · Kelas" atau "Anonim". */
    public function pengirim(): string
    {
        return $this->anonim() ? 'Anonim' : trim($this->nama.($this->kelas ? ' · '.$this->kelas : ''));
    }

    public function sudahDitanggapi(): bool
    {
        return $this->ditanggapi_pada !== null;
    }

    public function scopeBelumDitanggapi(Builder $query): void
    {
        $query->whereNull('ditanggapi_pada');
    }
}
