<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    /** @use HasFactory<\Database\Factories\AnnouncementFactory> */
    use HasFactory;

    protected $fillable = [
        'judul',
        'kategori',
        'ringkasan',
        'isi',
        'penulis_label',
        'penulis_id',
        'dipublikasikan',
        'dipublikasikan_pada',
    ];

    protected function casts(): array
    {
        return [
            'dipublikasikan' => 'boolean',
            'dipublikasikan_pada' => 'datetime',
        ];
    }

    public function penulis(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penulis_id');
    }

    /** Hanya pengumuman yang sudah tayang, terbaru dulu. */
    public function scopeTayang(Builder $query): void
    {
        $query->where('dipublikasikan', true)
            ->where(fn ($q) => $q->whereNull('dipublikasikan_pada')->orWhere('dipublikasikan_pada', '<=', now()))
            ->orderByDesc('dipublikasikan_pada')
            ->orderByDesc('id');
    }
}
