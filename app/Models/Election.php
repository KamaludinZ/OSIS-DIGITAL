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
        'hasil_dipublikasikan_pada',
        'hasil_dipublikasikan_oleh',
    ];

    protected static function booted(): void
    {
        // Pemilihan baru tercatat di periode aktif.
        static::creating(function (Election $e) {
            if (! $e->periode_id && ($aktif = OsisPeriod::aktif())) {
                $e->periode_id = $aktif->id;
            }
        });
        // Status / publikasi berubah -> rekap di halaman Hasil harus ikut berubah.
        static::saved(fn (Election $pemilihan) => \App\Services\LayananHasil::lupakanCache($pemilihan->id));
        static::saved(fn () => \App\Services\LayananNotifikasi::lupakan());
        // Jadwal/status diubah -> pengingat akun dari jadwal lama ditarik dari lonceng.
        static::saved(function (Election $pemilihan) {
            if ($pemilihan->wasChanged(['mulai', 'selesai', 'status'])) {
                app(\App\Services\LayananNotifikasi::class)->tarikPengingatBasi($pemilihan);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'mulai' => 'datetime',
            'selesai' => 'datetime',
            'status' => StatusPemilihan::class,
            'format_kandidat' => \App\Enums\FormatKandidat::class,
            'hasil_publik' => 'boolean',
            'hasil_dipublikasikan_pada' => 'datetime',
            'pengingat_terkirim' => 'array',
        ];
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class)->orderBy('nomor_urut');
    }

    public function pempublikasi(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'hasil_dipublikasikan_oleh');
    }

    public function periode(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(OsisPeriod::class, 'periode_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    /**
     * Pemilihan yang ditampilkan ke siswa: yang berjalan, atau yang terbaru — di periode
     * kepengurusan aktif (pemilihan lama tanpa periode tetap ikut terhitung).
     */
    public static function aktif(): ?self
    {
        $periode = OsisPeriod::aktif();

        return static::query()
            ->when($periode, fn (Builder $q) => $q->where(fn ($w) => $w->where('periode_id', $periode->id)->orWhereNull('periode_id')))
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
