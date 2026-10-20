<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    /** @use HasFactory<\Database\Factories\AnnouncementFactory> */
    use \App\Models\Concerns\IkutPeriode, HasFactory;

    /** Pengumuman lama tanpa periode tetap terbaca; tanpa periode aktif tidak difilter. */
    public function periodeLonggar(): bool
    {
        return true;
    }

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

    protected static function booted(): void
    {
        // Umpan pemberitahuan publik ikut diperbarui.
        static::saved(fn () => \App\Services\LayananNotifikasi::lupakan());
        // Begitu tayang, beri tahu akun yang berlangganan (terjadwal: lewat perintah terjadwal).
        static::saved(fn (Announcement $a) => app(\App\Services\LayananNotifikasi::class)->beritahuPengumuman($a));
        static::deleted(fn () => \App\Services\LayananNotifikasi::lupakan());
    }

    protected function casts(): array
    {
        return [
            'dipublikasikan' => 'boolean',
            'dipublikasikan_pada' => 'datetime',
            'diberitahukan_pada' => 'datetime',
        ];
    }

    public function penulis(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penulis_id');
    }

    /** Sudah tayang untuk publik saat ini? */
    public function sudahTayang(): bool
    {
        return $this->dipublikasikan && ($this->dipublikasikan_pada === null || $this->dipublikasikan_pada->lte(now()));
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
