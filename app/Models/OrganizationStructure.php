<?php

namespace App\Models;

use App\Models\Concerns\IkutPeriode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** Bagan struktur organisasi; satu per periode, otomatis dibatasi ke periode aktif. */
class OrganizationStructure extends Model
{
    /** @use HasFactory<\Database\Factories\OrganizationStructureFactory> */
    use HasFactory, IkutPeriode;

    public const RASIO = ['4:3' => 'Lebar (4:3)', '3:4' => 'Tinggi (3:4)'];

    protected $fillable = ['gambar', 'rasio', 'diunggah_oleh'];

    protected $attributes = ['rasio' => '4:3'];

    /** Bagan periode aktif (atau null bila belum diunggah / tidak ada periode aktif). */
    public static function aktif(): ?self
    {
        return static::query()->first();
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diunggah_oleh');
    }

    public function urlGambar(): string
    {
        return Storage::disk('public')->url($this->gambar);
    }

    public function tinggi(): bool
    {
        return $this->rasio === '3:4';
    }
}
