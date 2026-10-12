<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory;

    protected $fillable = [
        'nama',
        'deskripsi',
        'tanggal_mulai',
        'tanggal_selesai',
        'tempat',
        'divisi_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public function divisi(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'divisi_id');
    }

    /** Kegiatan yang belum selesai, terdekat dulu. */
    public function scopeMendatang(Builder $query): void
    {
        $query->whereDate('tanggal_selesai', '>=', today())->orderBy('tanggal_mulai');
    }

    /**
     * Kegiatan lain di tempat yang sama dengan rentang tanggal beririsan.
     * Dipakai untuk peringatan bentrok saat panitia menyusun jadwal.
     */
    public static function bentrok(?string $mulai, ?string $selesai, ?string $tempat, ?int $abaikanId = null): \Illuminate\Support\Collection
    {
        if (! $mulai || blank($tempat)) {
            return collect();
        }

        $selesai = $selesai ?: $mulai;

        return static::query()
            ->when($abaikanId, fn ($q) => $q->whereKeyNot($abaikanId))
            ->whereRaw('lower(trim(tempat)) = ?', [mb_strtolower(trim($tempat))])
            ->whereDate('tanggal_mulai', '<=', $selesai)
            ->whereDate('tanggal_selesai', '>=', $mulai)
            ->orderBy('tanggal_mulai')
            ->get();
    }
}
