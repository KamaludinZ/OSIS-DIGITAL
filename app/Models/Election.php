<?php

namespace App\Models;

use App\Enums\StatusPemilihan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Election extends Model
{
    /** @use HasFactory<\Database\Factories\ElectionFactory> */
    use HasFactory;

    protected $fillable = [
        'judul',
        'format_kandidat',
        'mulai',
        'selesai',
        'status',
        'hasil_publik',
    ];

    protected static function booted(): void
    {
        // Status / publikasi berubah -> rekap di halaman Hasil harus ikut berubah.
        static::saved(fn (Election $pemilihan) => \App\Services\LayananHasil::lupakanCache($pemilihan->id));
    }

    protected function casts(): array
    {
        return [
            'mulai' => 'datetime',
            'selesai' => 'datetime',
            'status' => StatusPemilihan::class,
            'format_kandidat' => \App\Enums\FormatKandidat::class,
            'hasil_publik' => 'boolean',
        ];
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class)->orderBy('nomor_urut');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    /** Pemilihan yang ditampilkan ke siswa: yang berjalan, atau yang terbaru. */
    public static function aktif(): ?self
    {
        return static::query()
            ->orderByRaw('case when status = ? then 0 else 1 end', [StatusPemilihan::Berjalan->value])
            ->latest('mulai')
            ->first();
    }

    public function scopeBerjalan(Builder $query): void
    {
        $query->where('status', StatusPemilihan::Berjalan);
    }

    /** Status berjalan DAN waktu sekarang di dalam rentang mulai–selesai. */
    public function sedangDibuka(): bool
    {
        return $this->status === StatusPemilihan::Berjalan
            && now()->between($this->mulai, $this->selesai);
    }
}
