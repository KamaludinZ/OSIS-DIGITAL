<?php

namespace App\Models;

use App\Enums\PlatformSosmed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SocialPost extends Model
{
    /** @use HasFactory<\Database\Factories\SocialPostFactory> */
    use HasFactory;

    protected $fillable = ['platform', 'judul', 'tautan', 'thumbnail', 'orientasi', 'dipublikasikan', 'pengunggah_id'];

    protected function casts(): array
    {
        return [
            'platform' => PlatformSosmed::class,
            'dipublikasikan' => 'boolean',
            'jumlah_klik' => 'integer',
        ];
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengunggah_id');
    }

    public function scopeTayang(Builder $query): void
    {
        $query->where('dipublikasikan', true)->latest()->latest('id');
    }

    public function urlThumbnail(): string
    {
        return Storage::disk('public')->url($this->thumbnail);
    }
}
