<?php

namespace App\Models;

use App\Enums\StatusProgramKerja;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkProgram extends Model
{
    /** @use HasFactory<\Database\Factories\WorkProgramFactory> */
    use \App\Models\Concerns\IkutPeriode, HasFactory;

    /** Program lama tanpa periode tetap terbaca; tanpa periode aktif tidak difilter. */
    public function periodeLonggar(): bool
    {
        return true;
    }

    /** Jumlah maksimum program unggulan (sorotan Beranda menampilkan 6). */
    public const BATAS_UNGGULAN = 6;

    protected $fillable = [
        'nama',
        'deskripsi',
        'divisi_id',
        'unggulan',
        'status',
        'capaian',
    ];

    protected function casts(): array
    {
        return [
            'unggulan' => 'boolean',
            'status' => StatusProgramKerja::class,
        ];
    }

    public function divisi(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'divisi_id');
    }

    /** Masih bisa menambah program unggulan baru? (abaikan program ini sendiri) */
    public static function kuotaUnggulanTersisa(?int $kecuali = null): int
    {
        return self::BATAS_UNGGULAN - static::where('unggulan', true)->when($kecuali, fn ($q) => $q->whereKeyNot($kecuali))->count();
    }
}
